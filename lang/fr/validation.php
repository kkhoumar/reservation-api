<?php

/*
 * Messages d'erreur des formulaires, en français simple.
 * Ils s'affichent juste sous la case concernée : pas besoin de répéter son nom.
 * Un écran peut toujours donner un message plus précis (méthode messages()).
 */
return [
    'accepted' => 'Cochez cette case pour continuer.',
    'after' => 'Choisissez une date après le :date.',
    'after_or_equal' => 'Choisissez une date à partir du :date.',
    'before' => 'Choisissez une date avant le :date.',
    'before_or_equal' => "Choisissez une date jusqu'au :date.",
    'between' => [
        'numeric' => 'Entrez un nombre entre :min et :max.',
        'string' => 'Écrivez entre :min et :max caractères.',
        'file' => 'Le fichier doit faire entre :min et :max Ko.',
        'array' => 'Choisissez entre :min et :max éléments.',
    ],
    'boolean' => 'Répondez par oui ou non.',
    'confirmed' => 'Les deux saisies ne sont pas pareilles.',
    'date' => 'Entrez une date valide.',
    'date_format' => 'Entrez une date au bon format.',
    'different' => 'Choisissez une valeur différente.',
    'digits' => 'Entrez :digits chiffres.',
    'digits_between' => 'Entrez entre :min et :max chiffres.',
    'email' => 'Entrez une adresse e-mail valide.',
    'exists' => "Ce choix n'existe pas. Choisissez dans la liste.",
    'file' => 'Choisissez un fichier.',
    'gt' => ['numeric' => 'Entrez un nombre plus grand que :value.'],
    'gte' => ['numeric' => 'Entrez un nombre au moins égal à :value.'],
    'image' => 'Choisissez une photo.',
    'in' => 'Choisissez une des propositions.',
    'integer' => 'Entrez un nombre entier, sans virgule.',
    'lt' => ['numeric' => 'Entrez un nombre plus petit que :value.'],
    'lte' => ['numeric' => 'Entrez un nombre au plus égal à :value.'],
    'max' => [
        'numeric' => 'Le nombre ne peut pas dépasser :max.',
        'string' => 'Pas plus de :max caractères.',
        'file' => 'Le fichier est trop lourd (:max Ko au plus).',
        'array' => 'Pas plus de :max éléments.',
    ],
    'mimes' => 'Ce type de fichier n\'est pas accepté (:values).',
    'mimetypes' => 'Ce type de fichier n\'est pas accepté (:values).',
    'min' => [
        'numeric' => 'Le nombre doit être au moins :min.',
        'string' => 'Au moins :min caractères.',
        'file' => 'Le fichier doit faire au moins :min Ko.',
        'array' => 'Choisissez au moins :min éléments.',
    ],
    'not_in' => 'Ce choix n\'est pas possible.',
    'numeric' => 'Entrez un nombre.',
    'regex' => "Le format n'est pas bon.",
    'required' => 'Ce champ est obligatoire.',
    'required_if' => 'Ce champ est obligatoire.',
    'required_with' => 'Ce champ est obligatoire.',
    'same' => 'Les deux saisies ne sont pas pareilles.',
    'size' => [
        'numeric' => 'Entrez :size.',
        'string' => 'Entrez exactement :size caractères.',
    ],
    'string' => 'Écrivez du texte.',
    'unique' => 'Cette valeur est déjà utilisée.',
    'uploaded' => "L'envoi du fichier a échoué. Réessayez.",

    'custom' => [],
    'attributes' => [],
];
