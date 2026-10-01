<?php
if ( PHP_SAPI !== 'cli' ) { exit; }
require dirname( __DIR__, 4 ) . '/wp-load.php';
$form = WPCF7_ContactForm::get_instance( 1311 );
if ( ! $form ) { throw new RuntimeException( 'Formulário não encontrado.' ); }
$properties = $form->get_properties();
$properties['form'] = '<div class="grv-contact-grid">
<div class="grv-field"><label for="grv-contact-name">[text* your-name id:grv-contact-name autocomplete:name placeholder "Como você se chama?"]<span class="grv-field-label">Nome</span></label></div>
<div class="grv-field"><label for="grv-contact-email">[email* your-email id:grv-contact-email autocomplete:email placeholder "voce@exemplo.com"]<span class="grv-field-label">E-mail</span></label></div>
<div class="grv-field grv-field-wide"><label for="grv-contact-subject">[text* your-subject id:grv-contact-subject placeholder "Qual é o assunto?"]<span class="grv-field-label">Assunto</span></label></div>
<div class="grv-field grv-field-wide"><label for="grv-contact-message">[textarea* your-message id:grv-contact-message 40x4 placeholder "Escreva sua dúvida ou sugestão"]<span class="grv-field-label">Mensagem</span></label></div>
<div class="grv-contact-consent grv-field-wide">[acceptance privacy] Li a <a href="' . esc_url( home_url( '/politica-de-privacidade/' ) ) . '">Política de Privacidade</a> e concordo com o uso dos dados para responder ao meu contato. [/acceptance]</div>
<div class="grv-contact-actions grv-field-wide">[submit "Enviar mensagem"]</div>
</div>';
$form->set_properties( $properties );
$form->save();
echo 'Formulário atualizado: ' . $form->id() . PHP_EOL;
