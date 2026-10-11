<?php

declare(strict_types=1);

$l = &$GLOBALS['TL_LANG']['tl_neo_websitecheck_settings'];

$l['brand_legend'] = 'Qui envoie le rapport';
$l['design_legend'] = 'Design';
$l['texts_legend'] = 'Configurations (textes, e-mail, PDF par langue)';
$l['mail_legend'] = 'E-mail';
$l['limits_legend'] = 'Limites';

$l['brand_name'] = ['Entreprise / agence', 'Affiché dans l’en-tête du rapport, dans l’e-mail et dans la signature. Vide = titre du site web.'];
$l['owner_name'] = ['Interlocuteur', 'La personne qui signe le rapport, p. ex. « Erich Nigg ».'];
$l['phone'] = ['Téléphone', 'Affiché dans le rapport et dans la signature de l’e-mail.'];
$l['website_url'] = ['Site web', 'Votre site web, p. ex. https://example.com'];
$l['contact_page'] = ['Page de contact', 'Cible des boutons « discuter du résultat » dans le rapport, l’e-mail et l’élément (l’élément peut la remplacer).'];
$l['privacy_page'] = ['Page de politique de confidentialité', 'Lien affiché sous le formulaire de contact.'];
$l['logo'] = ['Logo', 'PNG, JPG ou SVG pour l’en-tête du rapport et l’e-mail. Foncé sur fond clair.'];
$l['logo_dark'] = ['Logo pour fond sombre', 'Variante facultative pour l’en-tête coloré de l’e-mail ; vide = le logo ci-dessus.'];
$l['portrait'] = ['Photo portrait', 'Facultative, affichée dans l’encadré « et ensuite ? » et dans la signature de l’e-mail.'];
$l['color_primary'] = ['Couleur principale', 'PDF et e-mail : titres, encadrés, en-tête de l’e-mail (hex, p. ex. 1f2328). L’élément sur le site web est mis en forme par le CSS du thème (propriétés --nwc-*).'];
$l['color_accent'] = ['Couleur d’accent', 'PDF et e-mail : boutons, mises en évidence, filets (hex, p. ex. 0969da).'];
$l['texts_info'] = ['Configurations', ''];
$l['from_name'] = ['Nom de l’expéditeur', 'Vide = nom de l’entreprise.'];
$l['from_email'] = ['Adresse de l’expéditeur', 'Vide = config.yaml ou l’e-mail de l’administrateur Contao.'];
$l['reply_to'] = ['Adresse de réponse', 'Adresse à laquelle arrivent les réponses à l’e-mail de rapport.'];
$l['notify_email'] = ['Copie à', 'Reçoit une copie de chaque rapport avec le nom, l’e-mail et le choix newsletter du visiteur. Vide = pas de copie.'];
$l['checks_per_ip_hour'] = ['Analyses par visiteur et par heure', 'Protège contre les abus et les coûts. 0 = valeur de config.yaml (par défaut 2).'];
$l['checks_per_ip'] = ['Analyses par visiteur et par jour', '0 = valeur de config.yaml (par défaut 10).'];
$l['cache_hours'] = ['Cache en heures', 'Même domaine à nouveau dans ce délai : résultat enregistré. 0 = config.yaml (24).'];
$l['retention_days'] = ['Conservation en jours', 'Les analyses, coordonnées des contacts comprises, sont supprimées après ce délai. 0 = config.yaml (90).'];
$l['max_pages'] = ['Pages par analyse', 'Page d’accueil plus les sous-pages principales, 8 au maximum. 0 = config.yaml (5).'];

$l['edit'] = ['Modifier', 'Modifier les paramètres'];
$l['configs'] = ['Configurations', 'Concevoir l’e-mail, le PDF et les textes par langue'];
$l['preview_pdf'] = ['Aperçu du PDF', 'Générer le rapport PDF avec la dernière analyse terminée (ou des données de démonstration)'];
$l['preview_mail'] = ['Aperçu de l’e-mail', 'Générer l’e-mail de rapport avec la dernière analyse terminée (ou des données de démonstration)'];
$l['textsButton'] = 'Modifier les configurations (%d)';
$l['textsNone'] = 'Aucune configuration pour l’instant. Créez-en une par langue : c’est là que vous concevez l’e-mail et le rapport PDF, l’objet, l’expéditeur et les textes de l’élément. D’ici là, les templates fournis avec l’extension s’appliquent.';
$l['textsList'] = 'Existantes : %s';
