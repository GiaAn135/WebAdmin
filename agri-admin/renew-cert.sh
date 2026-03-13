#!/bin/bash

# Script to renew Let's Encrypt certificate for staging.agrii.asia

DOMAIN="staging.agrii.asia"
CERT_PATH="/home/dev01/nong_dan_so/agri-admin/certs"
PUBLIC_PATH="/home/dev01/nong_dan_so/agri-admin/public"
NGINX_CONTAINER="nginx"

echo "$(date): Starting certificate renewal for $DOMAIN"

# Run certbot to renew certificate
docker run --rm \
  --name certbot-renew-$(date +%s) \
  -v ${PUBLIC_PATH}:/var/www/html/public \
  -v ${CERT_PATH}:/etc/letsencrypt \
  certbot/certbot:v5.1.0 renew \
  --webroot \
  --webroot-path=/var/www/html/public \
  --quiet

# Check if renewal was successful
if [ $? -eq 0 ]; then
    echo "$(date): Certificate renewal successful"
    
    # Reload nginx to use new certificate
    docker exec ${NGINX_CONTAINER} nginx -s reload
    
    if [ $? -eq 0 ]; then
        echo "$(date): Nginx reloaded successfully"
    else
        echo "$(date): ERROR: Failed to reload nginx"
        exit 1
    fi
else
    echo "$(date): Certificate renewal failed or no renewal needed"
    exit 0
fi

