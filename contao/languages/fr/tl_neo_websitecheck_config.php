<?php

declare(strict_types=1);

$l = &$GLOBALS['TL_LANG']['tl_neo_websitecheck_config'];

$l['title_legend'] = 'Configuration';
$l['mail_legend'] = 'E-mail au visiteur';
$l['mail_design_legend'] = 'Concevoir l’e-mail';
$l['pdf_legend'] = 'Rapport PDF';
$l['pdf_design_legend'] = 'Concevoir le rapport PDF';
$l['owner_legend'] = 'Votre copie';
$l['element_legend'] = 'L’élément sur la page';
$l['fe_result'] = ['Résultat sur la page', 'Court : note, une phrase courte et le renvoi au PDF. Complet : en plus les scores, tous les domaines, les mesures et les points forts. Le PDF contient toujours tout.'];
$l['fe_result_options'] = ['compact' => 'Court (recommandé)', 'full' => 'Complet'];
$l['expert_legend'] = 'Copie interne en template Twig';

$l['title'] = ['Nom', 'Nom interne, p. ex. « Allemand » ou « Anglais – hôtels ».'];
$l['language'] = ['Langue', 'Un élément sans attribution explicite utilise la configuration dont la langue correspond à sa page.'];
$l['mail_subject'] = ['Objet', 'Variables autorisées. Par défaut : « Votre analyse de site web pour {{domain}} : note {{grade}} · {{grade_word}} »'];
$l['mail_from_name'] = ['Nom de l’expéditeur', 'Vide = paramètres / nom de l’entreprise.'];
$l['mail_from_email'] = ['Adresse de l’expéditeur', 'Vide = paramètres / config.yaml / adresse de l’administrateur Contao.'];
$l['mail_reply_to'] = ['Adresse de réponse', 'Vide = paramètres.'];
$l['mail_owner_subject'] = ['Objet de votre copie', 'Par défaut : « [Analyse de site web] {{domain}} · note {{grade}} · {{firstname}} »'];
$l['mail_design'] = ['E-mail', 'Ajoutez des blocs à gauche, cliquez dessus dans l’aperçu et modifiez-les à droite. « Charger un modèle » applique une structure toute prête.'];
$l['pdf_filename'] = ['Nom du fichier PDF', 'Avec variables, p. ex. website-check-{{domain}}-{{date}}.pdf ({{grade}}, {{firstname}}, {{lastname}}, {{company}} fonctionnent aussi).'];
$l['pdf_design'] = ['Rapport PDF', 'Composez page par page : couverture, blocs de données, vos propres textes et images, sauts de page. L’aperçu utilise la dernière analyse terminée ou des données de démonstration.'];
$l['mail_body'] = ['E-mail en code Twig (mode avancé)', 'Réservé aux administrateurs. Se modifie dans l’éditeur via « Avancé (Twig) ». S’il est rempli, ce code remplace la mise en page visuelle.'];
$l['owner_body'] = ['Votre copie en template Twig', 'Vide = template fourni avec l’extension.'];
$l['pdf_body'] = ['Rapport PDF en code Twig (mode avancé)', 'Réservé aux administrateurs. Se modifie dans l’éditeur via « Avancé (Twig) ». S’il est rempli, ce code remplace la mise en page visuelle.'];

$l['fe_url_label'] = ['Libellé au-dessus du champ d’adresse', 'Par défaut : « Adresse de votre site web »'];
$l['fe_submit'] = ['Bouton « Analyser maintenant »', ''];
$l['fe_gate_title'] = ['Formulaire de contact : titre', 'Par défaut : « Votre résultat est prêt »'];
$l['fe_gate_text'] = ['Formulaire de contact : texte', 'Par défaut : « Où pouvons-nous vous l’envoyer ? … »'];
$l['fe_gate_legal'] = ['Formulaire de contact : mention de confidentialité', 'Le lien vers la page de confidentialité est ajouté automatiquement.'];
$l['fe_newsletter'] = ['Texte de la case newsletter', ''];
$l['fe_gate_submit'] = ['Formulaire de contact : bouton', 'Par défaut : « Afficher le résultat »'];
$l['fe_disclaimer'] = ['Résultat : remarque', 'Par défaut : « Évaluation automatique basée sur … »'];
$l['fe_cta'] = ['Résultat : bouton', 'Par défaut : « Discuter du résultat gratuitement »'];

$l['new'] = ['Nouvelle configuration', 'Ajouter une configuration pour une autre langue ou un autre usage'];
$l['edit'] = ['Modifier', 'Modifier la configuration ID %s'];
$l['copy'] = ['Dupliquer', 'Dupliquer la configuration ID %s'];
$l['delete'] = ['Supprimer', 'Supprimer la configuration ID %s'];
$l['show'] = ['Détails', 'Afficher les détails de l’ID %s'];
$l['preview_pdf'] = ['Aperçu PDF', 'Ouvrir le rapport conçu au format PDF'];
$l['preview_mail'] = ['Aperçu de l’e-mail', 'Ouvrir l’e-mail conçu dans le navigateur'];
$l['automatic'] = 'Automatique (selon la langue de la page)';
$l['element_config'] = ['Configuration', 'Quelle configuration de l’analyse de site (textes, e-mail, PDF) cet élément utilise. Automatique = celle qui correspond à la langue de cette page.'];

// Multilingual sites: links and newsletter per configuration (= per language); empty = global value.
$l['links_legend'] = 'Pages de cette langue';
$l['contact_page'] = ['Page de contact', 'Cible de « Discuter du résultat » dans le formulaire, l’e-mail et le PDF. Vide = page de contact des paramètres. L’élément de contenu peut la remplacer par sa propre page de redirection. Sans page (ici ou dans les paramètres), le bouton est omis – l’extension ne crée jamais de lien d’elle-même.'];
$l['privacy_page'] = ['Page de confidentialité', 'Lien dans l’étape de contact du formulaire, dans l’e-mail et le PDF. Vide = page de confidentialité des paramètres. Sans page, aucun lien n’est affiché – veuillez la définir.'];
$l['newsletter_legend'] = 'Newsletter de cette langue';
$l['nl_list_id'] = ['Liste Brevo', 'Liste pour les inscriptions issues de l’analyse dans cette langue. Vide = liste du fichier de configuration (newsletter.brevo_list_id).'];
$l['nl_template_id'] = ['Modèle de double opt-in', 'E-mail de confirmation dans cette langue. Vide = modèle du fichier de configuration (newsletter.brevo_template_id).'];
$l['nl_redirect_page'] = ['Page de confirmation', 'Page affichée après un clic sur le lien de confirmation. Vide = newsletter.brevo_redirect_url. Sans page de confirmation, pas d’inscription à la newsletter.'];
$l['nl_list_id_contao'] = ['Liste de diffusion (Contao)', 'Liste de diffusion Contao pour les inscriptions issues de l’analyse dans cette langue.'];
$l['nl_redirect_page_contao'] = ['Page de confirmation', 'Page contenant le module Contao « S’abonner » : le lien de confirmation de l’e-mail y mène et active l’inscription. Le texte de l’e-mail provient de ce module. Sans page de confirmation, pas d’inscription à la newsletter.'];
