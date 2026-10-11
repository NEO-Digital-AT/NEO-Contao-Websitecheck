<?php

declare(strict_types=1);

$l = &$GLOBALS['TL_LANG']['tl_neo_websitecheck_config'];

$l['title_legend'] = 'Configuración';
$l['mail_legend'] = 'Correo al visitante';
$l['mail_design_legend'] = 'Diseñar el correo';
$l['pdf_legend'] = 'Informe PDF';
$l['pdf_design_legend'] = 'Diseñar el informe PDF';
$l['owner_legend'] = 'Su copia';
$l['element_legend'] = 'El elemento en la página';
$l['fe_result'] = ['Resultado en la página', 'Breve: nota, una frase corta y la indicación del PDF. Completo: además puntuaciones, todas las áreas, medidas y puntos fuertes. El PDF siempre lo incluye todo.'];
$l['fe_result_options'] = ['compact' => 'Breve (recomendado)', 'full' => 'Completo'];
$l['expert_legend'] = 'Copia interna como plantilla Twig';

$l['title'] = ['Nombre', 'Nombre interno, p. ej. “Español” o “Inglés – hoteles”.'];
$l['language'] = ['Idioma', 'Un elemento sin asignación explícita usa la configuración cuyo idioma coincide con el de su página.'];
$l['mail_subject'] = ['Asunto', 'Se admiten marcadores. Por defecto: “Su análisis web de {{domain}}: nota {{grade}} · {{grade_word}}”'];
$l['mail_from_name'] = ['Nombre del remitente', 'Vacío = ajustes / nombre de la empresa.'];
$l['mail_from_email'] = ['Dirección del remitente', 'Vacío = ajustes / config.yaml / dirección del administrador de Contao.'];
$l['mail_reply_to'] = ['Dirección de respuesta', 'Vacío = ajustes.'];
$l['mail_owner_subject'] = ['Asunto de su copia', 'Por defecto: “[Análisis web] {{domain}} · nota {{grade}} · {{firstname}}”'];
$l['mail_design'] = ['Correo', 'Añada bloques a la izquierda, haga clic en ellos en la vista previa y edítelos a la derecha. “Cargar plantilla” aplica una estructura ya preparada.'];
$l['pdf_filename'] = ['Nombre del archivo PDF', 'Con marcadores, p. ej. website-check-{{domain}}-{{date}}.pdf (también funcionan {{grade}}, {{firstname}}, {{lastname}}, {{company}}).'];
$l['pdf_design'] = ['Informe PDF', 'Componga página a página: portada, bloques de datos, sus propios textos e imágenes, saltos de página. La vista previa usa el último análisis terminado o datos de ejemplo.'];
$l['mail_body'] = ['Correo como código Twig (modo avanzado)', 'Solo para administradores. Se edita en el diseñador mediante “Avanzado (Twig)”. Si está relleno, se usa este código en lugar del diseño visual.'];
$l['owner_body'] = ['Su copia como plantilla Twig', 'Vacío = plantilla incluida.'];
$l['pdf_body'] = ['Informe PDF como código Twig (modo avanzado)', 'Solo para administradores. Se edita en el diseñador mediante “Avanzado (Twig)”. Si está relleno, se usa este código en lugar del diseño visual.'];

$l['fe_url_label'] = ['Etiqueta sobre el campo de dirección', 'Por defecto: “Dirección de su sitio web”'];
$l['fe_submit'] = ['Botón “Analizar ahora”', ''];
$l['fe_gate_title'] = ['Formulario de contacto: título', 'Por defecto: “Su resultado está listo”'];
$l['fe_gate_text'] = ['Formulario de contacto: texto', 'Por defecto: “¿Adónde se lo enviamos? …”'];
$l['fe_gate_legal'] = ['Formulario de contacto: aviso de privacidad', 'El enlace a la página de privacidad se añade automáticamente.'];
$l['fe_newsletter'] = ['Texto de la casilla del boletín', ''];
$l['fe_gate_submit'] = ['Formulario de contacto: botón', 'Por defecto: “Ver resultado”'];
$l['fe_disclaimer'] = ['Resultado: nota', 'Por defecto: “Evaluación automática basada en …”'];
$l['fe_cta'] = ['Resultado: botón', 'Por defecto: “Comentar el resultado, sin coste”'];

$l['new'] = ['Nueva configuración', 'Añadir una configuración para otro idioma u otro uso'];
$l['edit'] = ['Editar', 'Editar la configuración ID %s'];
$l['copy'] = ['Duplicar', 'Duplicar la configuración ID %s'];
$l['delete'] = ['Eliminar', 'Eliminar la configuración ID %s'];
$l['show'] = ['Detalles', 'Mostrar los detalles del ID %s'];
$l['preview_pdf'] = ['Vista previa del PDF', 'Abrir el informe diseñado como PDF'];
$l['preview_mail'] = ['Vista previa del correo', 'Abrir el correo diseñado en el navegador'];
$l['automatic'] = 'Automático (según el idioma de la página)';
$l['element_config'] = ['Configuración', 'Qué configuración del análisis web (textos, correo, PDF) usa este elemento. Automático = la que corresponde al idioma de esta página.'];

// Multilingual sites: links and newsletter per configuration (= per language); empty = global value.
$l['links_legend'] = 'Páginas de este idioma';
$l['contact_page'] = ['Página de contacto', 'Destino de “Comentar el resultado” en el formulario, el correo y el PDF. Vacío = página de contacto de los ajustes. El elemento de contenido puede sustituirla por su propia página de redirección. Sin página (aquí o en los ajustes) el botón no se muestra: el módulo nunca crea un enlace por su cuenta.'];
$l['privacy_page'] = ['Página de privacidad', 'Enlace en el paso de contacto del formulario, en el correo y en el PDF. Vacío = página de privacidad de los ajustes. Sin página no se muestra ningún enlace, así que indíquela, por favor.'];
$l['newsletter_legend'] = 'Boletín (Brevo) de este idioma';
$l['nl_list_id'] = ['Lista de Brevo', 'Lista para las altas desde el análisis en este idioma. Vacío = lista del archivo de configuración (newsletter.brevo_list_id).'];
$l['nl_template_id'] = ['Plantilla de doble confirmación', 'Correo de confirmación en este idioma. Vacío = plantilla del archivo de configuración (newsletter.brevo_template_id).'];
$l['nl_redirect_page'] = ['Página de confirmación', 'Página que se abre tras hacer clic en el enlace de confirmación. Vacío = newsletter.brevo_redirect_url. Sin página de confirmación no hay alta en el boletín.'];
