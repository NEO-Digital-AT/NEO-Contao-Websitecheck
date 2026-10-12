<?php

declare(strict_types=1);

$l = &$GLOBALS['TL_LANG']['tl_neo_websitecheck_settings'];

$l['brand_legend'] = 'Quién envía el informe';
$l['design_legend'] = 'Diseño';
$l['texts_legend'] = 'Configuraciones (textos, correo, PDF por idioma)';
$l['mail_legend'] = 'Correo electrónico';
$l['limits_legend'] = 'Límites';

$l['brand_name'] = ['Empresa / agencia', 'Aparece en la cabecera del informe, en el correo y en la firma. Vacío = título del sitio web.'];
$l['owner_name'] = ['Persona de contacto', 'La persona que firma el informe, p. ej. "Juan Pérez".'];
$l['phone'] = ['Teléfono', 'Aparece en el informe y en la firma del correo.'];
$l['website_url'] = ['Sitio web', 'Su sitio web, p. ej. https://example.com'];
$l['contact_page'] = ['Página de contacto', 'Destino de los botones "Comentar el resultado" en el informe, el correo y el elemento (el elemento puede sustituirla).'];
$l['privacy_page'] = ['Página de política de privacidad', 'Se enlaza bajo el formulario de contacto.'];
$l['logo'] = ['Logotipo', 'PNG, JPG o SVG para la cabecera del informe y el correo. Oscuro sobre fondo claro.'];
$l['logo_dark'] = ['Logotipo para fondo oscuro', 'Variante opcional para la cabecera de color del correo; vacío = el logotipo de arriba.'];
$l['portrait'] = ['Foto de retrato', 'Opcional; aparece en el recuadro "Qué pasa ahora" y en la firma del correo.'];
$l['color_primary'] = ['Color principal', 'PDF y correo: titulares, recuadros, cabecera del correo (hex, p. ej. 1f2328). El elemento del sitio web se diseña con el CSS del tema (propiedades --nwc-*).'];
$l['color_accent'] = ['Color de acento', 'PDF y correo: botones, resaltados, líneas (hex, p. ej. 0969da).'];
$l['texts_info'] = ['Configuraciones', ''];
$l['from_name'] = ['Nombre del remitente', 'Vacío = nombre de la empresa.'];
$l['from_email'] = ['Dirección del remitente', 'Vacío = config.yaml o el correo del administrador de Contao.'];
$l['reply_to'] = ['Dirección de respuesta', 'Adónde llegan las respuestas al correo del informe.'];
$l['notify_email'] = ['Copia a', 'Recibe una copia de cada informe con el nombre, el correo y la elección de boletín del visitante. Vacío = sin copia.'];
$l['checks_per_ip_hour'] = ['Análisis por visitante y hora', 'Protege contra abusos y costes. 0 = valor de config.yaml (por defecto 2).'];
$l['checks_per_ip'] = ['Análisis por visitante y día', '0 = valor de config.yaml (por defecto 10).'];
$l['cache_hours'] = ['Caché en horas', 'El mismo dominio otra vez dentro de este plazo: resultado guardado. 0 = config.yaml (24).'];
$l['retention_days'] = ['Conservación en días', 'Los análisis, incluidos los datos de contacto, se eliminan pasado este plazo. 0 = config.yaml (90).'];
$l['max_pages'] = ['Páginas por análisis', 'Página de inicio más las subpáginas principales, como máximo 8. 0 = config.yaml (5).'];

$l['edit'] = ['Editar', 'Editar los ajustes'];
$l['configs'] = ['Configuraciones', 'Diseñar el correo, el PDF y los textos por idioma'];
$l['preview_pdf'] = ['Vista previa del PDF', 'Generar el informe PDF con el último análisis terminado (o datos de ejemplo)'];
$l['preview_mail'] = ['Vista previa del correo', 'Generar el correo del informe con el último análisis terminado (o datos de ejemplo)'];
$l['textsButton'] = 'Editar configuraciones (%d)';
$l['textsNone'] = 'Todavía no hay ninguna configuración. Cree una por idioma: ahí se diseñan el correo y el informe PDF, el asunto, el remitente y los textos del elemento. Mientras tanto se aplican las plantillas incluidas.';
$l['textsList'] = 'Existentes: %s';

$l['api_legend'] = 'Claves e IA';
$l['api_info'] = 'De dónde vienen las claves';
$l['psi_api_key'] = ['Clave de PageSpeed', 'Clave de la API de Google para PageSpeed Insights (medición del tiempo de carga). Se guarda cifrada y no se vuelve a mostrar; dejar vacío para conservar la clave guardada.'];
$l['ai_api_key'] = ['Clave de IA', 'Clave del proveedor de IA (p. ej. Requesty) para la valoración «con los ojos de un visitante». Se guarda cifrada; dejar vacío para conservarla.'];
$l['ai_model'] = ['Modelo de IA', 'En cuanto se conoce una clave de IA, selección entre los modelos del proveedor. Vacío = valor de la configuración del servidor.'];
$l['ai_base_url'] = ['Dirección del proveedor de IA', 'Dirección compatible con OpenAI, p. ej. https://router.eu.requesty.ai/v1. Vacío = configuración del servidor.'];
$l['apiIntro'] = 'Los valores introducidos aquí prevalecen sobre la configuración del servidor (config.yaml / entorno). Lo que aquí está vacío viene de allí.';
$l['apiPsi'] = 'PageSpeed';
$l['apiAi'] = 'IA';
$l['apiModel'] = 'Modelo en uso';
$l['apiFromDb'] = 'guardada aquí en el back end';
$l['apiFromConfig'] = 'de la configuración del servidor';
$l['apiMissing'] = 'falta — esta parte del check se omite';
$l['modelFromConfig'] = '– configuración del servidor (%s) –';
$l['test_api'] = ['Probar conexiones', ''];
$l['checks_total_hour'] = ['Nuevos checks en total por hora', 'Todos los visitantes juntos; protege el presupuesto de IA del uso automatizado. Los resultados en caché no cuentan. 0 = config.yaml (20).'];
$l['checks_total_day'] = ['Nuevos checks en total por día', 'Todos los visitantes juntos. 0 = config.yaml (60).'];
