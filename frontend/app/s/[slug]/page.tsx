"use client";

import { useEffect, useState } from "react";
import { useParams } from "next/navigation";
import { getSpinConfig, ApiError } from "../../../lib/api";
import type { SpinConfig } from "../../../lib/api";
import styles from "./page.module.css";

export default function SpinPage() {
  const params = useParams();
  const slug = typeof params.slug === "string" ? params.slug : "";

  const [config, setConfig] = useState<SpinConfig | null>(null);
  const [notFound, setNotFound] = useState(false);
  const [error, setError] = useState<string | null>(null);

  useEffect(() => {
    if (!slug) return;
    getSpinConfig(slug)
      .then(setConfig)
      .catch((err) => {
        if (err instanceof ApiError && err.status === 404) {
          setNotFound(true);
        } else {
          setError("Nepodařilo se načíst konfiguraci. Zkuste to prosím znovu.");
        }
      });
  }, [slug]);

  if (notFound) {
    return (
      <div className={styles.page}>
        <main className={styles.main}>
          <h1 className={styles.title}>Konfigurace nenalezena</h1>
          <p className={styles.subtitle}>
            Konfigurace s tímto odkazem neexistuje.
          </p>
          <a href="/" className={styles.link}>
            ← Vytvořit novou konfiguraci
          </a>
        </main>
      </div>
    );
  }

  if (error) {
    return (
      <div className={styles.page}>
        <main className={styles.main}>
          <p className={styles.genericError}>{error}</p>
          <a href="/" className={styles.link}>
            ← Zpět na hlavní stránku
          </a>
        </main>
      </div>
    );
  }

  if (!config) {
    return (
      <div className={styles.page}>
        <main className={styles.main}>
          <p className={styles.loading}>Načítám…</p>
        </main>
      </div>
    );
  }

  return (
    <div className={styles.page}>
      <main className={styles.main}>
        <h1 className={styles.title}>{config.name}</h1>
        <p className={styles.subtitle}>
          {config.options.length} možnost{config.options.length === 1 ? "" : config.options.length < 5 ? "i" : "í"}
        </p>
        <ul className={styles.optionsList}>
          {config.options.map((option, i) => (
            <li key={i} className={styles.optionItem}>
              {option}
            </li>
          ))}
        </ul>
        <a href="/" className={styles.link}>
          ← Vytvořit novou konfiguraci
        </a>
      </main>
    </div>
  );
}
