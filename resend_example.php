<?php

$resend = Resend::client('re_g9kX831M_Akpi3wFpCkpveUxAq7WyEwEP');

$resend->emails->send([
  'from' => 'onboarding@resend.dev',
  'to' => 'aagripromis@gmail.com',
  'subject' => 'Hello World',
  'html' => '<p>Congrats on sending your <strong>first email</strong>!</p>'
]);