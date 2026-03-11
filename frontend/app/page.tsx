"use client";

import { useState } from "react";
import { useRouter } from "next/navigation";
import { createSpinConfig, ApiError } from "../lib/api";
import type { VisualMode } from "../lib/api";
import styles from "./page.module.css";

interface FormState {
  name: string;
  options: string;
  removeAfterPick: boolean;
  visualMode: VisualMode;
}

interface FormErrors {
  name?: string;
  options?: string;
}

const initialState: FormState = {
  name: "",
  options: "",
  removeAfterPick: true,
  visualMode: "classic",
};

function validateForm(state: FormState): FormErrors {
  const errors: FormErrors = {};

  if (!state.name.trim()) {
    errors.name = "Název nesmí být prázdný.";
  }

  const optionLines = state.options
    .split("\n")
    .map((line) => line.trim())
    .filter((line) => line.length > 0);

  if (optionLines.length === 0) {
    errors.options = "Zadejte alespoň jednu možnost.";
  }

  return errors;
}

export default function BuilderPage() {
  const router = useRouter();
  const [form, setForm] = useState<FormState>(initialState);
  const [errors, setErrors] = useState<FormErrors>({});
  const [submitted, setSubmitted] = useState(false);
  const [submitting, setSubmitting] = useState(false);
  const [genericError, setGenericError] = useState<string | null>(null);

  function handleChange(
    e: React.ChangeEvent<
      HTMLInputElement | HTMLTextAreaElement | HTMLSelectElement
    >,
  ) {
    const { name, value, type } = e.target;
    const checked =
      type === "checkbox" ? (e.target as HTMLInputElement).checked : undefined;

    setForm((prev) => ({
      ...prev,
      [name]: type === "checkbox" ? checked : value,
    }));

    if (submitted) {
      setErrors(
        validateForm({
          ...form,
          [name]: type === "checkbox" ? checked : value,
        }),
      );
    }
  }

  async function handleSubmit(e: React.FormEvent) {
    e.preventDefault();
    setSubmitted(true);
    setGenericError(null);
    const validationErrors = validateForm(form);
    setErrors(validationErrors);

    if (Object.keys(validationErrors).length > 0) {
      return;
    }

    const options = form.options
      .split("\n")
      .map((line) => line.trim())
      .filter((line) => line.length > 0);

    setSubmitting(true);
    try {
      const config = await createSpinConfig({
        name: form.name.trim(),
        options,
        removeAfterPick: form.removeAfterPick,
        visualMode: form.visualMode,
      });
      router.push(`/s/${config.slug}`);
    } catch (err) {
      if (err instanceof ApiError && err.status === 400 && err.body.details) {
        const backendErrors: FormErrors = {};
        const details = err.body.details;
        if (details.name?.length) backendErrors.name = details.name[0];
        if (details.options?.length) backendErrors.options = details.options[0];
        setErrors(backendErrors);
      } else {
        setGenericError("Nepodařilo se vytvořit konfiguraci. Zkuste to prosím znovu.");
      }
    } finally {
      setSubmitting(false);
    }
  }

  return (
    <div className={styles.page}>
      <main className={styles.main}>
        <h1 className={styles.title}>Prďolotoč</h1>
        <p className={styles.subtitle}>
          Vytvořte si vlastní roztočitelný seznam možností.
        </p>

        {genericError && (
          <p className={styles.genericError}>{genericError}</p>
        )}

        <form className={styles.form} onSubmit={handleSubmit} noValidate>
          <div className={styles.field}>
            <label htmlFor="name" className={styles.label}>
              Název konfigurace
            </label>
            <input
              id="name"
              name="name"
              type="text"
              value={form.name}
              onChange={handleChange}
              placeholder="Např. Oběd"
              className={`${styles.input} ${errors.name ? styles.inputError : ""}`}
              maxLength={100}
            />
            {errors.name && (
              <span className={styles.errorMsg}>{errors.name}</span>
            )}
          </div>

          <div className={styles.field}>
            <label htmlFor="options" className={styles.label}>
              Možnosti{" "}
              <span className={styles.hint}>(každá na nový řádek)</span>
            </label>
            <textarea
              id="options"
              name="options"
              value={form.options}
              onChange={handleChange}
              placeholder={"Pizza\nBurger\nSalát"}
              rows={6}
              className={`${styles.textarea} ${errors.options ? styles.inputError : ""}`}
            />
            {errors.options && (
              <span className={styles.errorMsg}>{errors.options}</span>
            )}
          </div>

          <div className={styles.field}>
            <label className={styles.checkboxLabel}>
              <input
                id="removeAfterPick"
                name="removeAfterPick"
                type="checkbox"
                checked={form.removeAfterPick}
                onChange={handleChange}
                className={styles.checkbox}
              />
              Odstranit možnost po výběru
            </label>
          </div>

          <div className={styles.field}>
            <label htmlFor="visualMode" className={styles.label}>
              Vizuální mód
            </label>
            <select
              id="visualMode"
              name="visualMode"
              value={form.visualMode}
              onChange={handleChange}
              className={styles.select}
            >
              <option value="classic">Classic</option>
              <option value="slow">Slow</option>
              <option value="chaotic">Chaotic</option>
            </select>
          </div>

          <div className={styles.actions}>
            <button type="submit" className={styles.primaryBtn} disabled={submitting}>
              {submitting ? "Ukládám…" : "Roztočit!"}
            </button>
          </div>
        </form>
      </main>
    </div>
  );
}
