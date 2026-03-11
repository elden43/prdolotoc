<?php

namespace App\Controller;

use App\Entity\SpinConfig;
use App\Exception\ValidationException;
use App\Repository\SpinConfigRepository;
use App\Service\SlugGeneratorService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

final class SpinConfigController
{
    private const VALID_VISUAL_MODES = ['classic', 'slow', 'chaotic'];

    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly SpinConfigRepository $repository,
        private readonly SlugGeneratorService $slugGenerator,
    ) {}

    #[Route('/api/spin-configs', name: 'api_spin_configs_create', methods: ['POST'])]
    public function create(Request $request): JsonResponse
    {
        $data = json_decode((string) $request->getContent(), true, 512, JSON_THROW_ON_ERROR);

        $errors = $this->validate($data);
        if ($errors !== []) {
            throw new ValidationException($errors);
        }

        /** @var string $name */
        $name = trim((string) $data['name']);

        /** @var list<string> $options */
        $rawOptions = is_array($data['options']) ? $data['options'] : [];
        $options = array_values(array_filter(
            array_map(static fn ($o) => trim((string) $o), $rawOptions),
            static fn ($o) => $o !== '',
        ));

        $removeAfterPick = isset($data['removeAfterPick']) ? (bool) $data['removeAfterPick'] : true;
        $visualMode = isset($data['visualMode']) ? (string) $data['visualMode'] : 'classic';

        $slug = $this->slugGenerator->generate(
            $name,
            fn (string $s): bool => $this->repository->findOneBy(['slug' => $s]) !== null,
        );

        $spinConfig = new SpinConfig();
        $spinConfig->setName($name);
        $spinConfig->setSlug($slug);
        $spinConfig->setOptions($options);
        $spinConfig->setRemoveAfterPick($removeAfterPick);
        $spinConfig->setVisualMode($visualMode);

        $this->em->persist($spinConfig);
        $this->em->flush();

        return new JsonResponse($this->normalize($spinConfig), JsonResponse::HTTP_CREATED);
    }

    /**
     * @param mixed $data
     * @return array<string, list<string>>
     */
    private function validate(mixed $data): array
    {
        $errors = [];

        if (!is_array($data)) {
            $errors['request'] = ['Request body must be a JSON object.'];
            return $errors;
        }

        // Validate name
        if (!isset($data['name']) || !is_string($data['name']) || trim($data['name']) === '') {
            $errors['name'] = ['This value should not be blank.'];
        } elseif (mb_strlen(trim($data['name'])) > 100) {
            $errors['name'] = ['This value is too long. It should have 100 characters or less.'];
        }

        // Validate options
        if (!isset($data['options'])) {
            $errors['options'] = ['This value should not be null.'];
        } elseif (!is_array($data['options'])) {
            $errors['options'] = ['This value should be of type array.'];
        } else {
            $nonEmpty = array_filter(
                array_map(static fn ($o) => trim((string) $o), $data['options']),
                static fn ($o) => $o !== '',
            );
            if (count($nonEmpty) === 0) {
                $errors['options'] = ['At least one option is required.'];
            }
        }

        // Validate removeAfterPick (optional)
        if (isset($data['removeAfterPick']) && !is_bool($data['removeAfterPick'])) {
            $errors['removeAfterPick'] = ['This value should be of type boolean.'];
        }

        // Validate visualMode (optional)
        if (isset($data['visualMode'])) {
            if (!is_string($data['visualMode']) || !in_array($data['visualMode'], self::VALID_VISUAL_MODES, true)) {
                $errors['visualMode'] = [sprintf(
                    'This value is not valid. Allowed values: %s.',
                    implode(', ', self::VALID_VISUAL_MODES),
                )];
            }
        }

        return $errors;
    }

    /** @return array<string, mixed> */
    private function normalize(SpinConfig $config): array
    {
        return [
            'id'              => (string) $config->getId(),
            'slug'            => $config->getSlug(),
            'name'            => $config->getName(),
            'options'         => $config->getOptions(),
            'removeAfterPick' => $config->isRemoveAfterPick(),
            'visualMode'      => $config->getVisualMode(),
            'createdAt'       => $config->getCreatedAt()->format(\DateTimeInterface::ATOM),
        ];
    }
}
