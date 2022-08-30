<?php

return [
    'host' => getenv('KEYCLOAK_DOMAIN', ''),

    'realm' => getenv('KEYCLOAK_REALM', ''),

    'client_id' => getenv('KEYCLOAK_CLIENT_ID', ''),

    'client_secret' => getenv('KEYCLOAK_CLIENT_SECRET', ''),

    'admin_username' => getenv('KEYCLOAK_ADMIN_USERNAME', ''),

    'admin_password' => getenv('KEYCLOAK_ADMIN_PASSWORD', ''),
];
