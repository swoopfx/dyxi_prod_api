#!/bin/bash
mkdir -p data/keys
openssl genpkey -algorithm RSA -out data/keys/private.pem -pkeyopt rsa_keygen_bits:2048
openssl rsa -pubout -in data/keys/private.pem -out data/keys/public.pem
chmod 600 data/keys/private.pem
chmod 644 data/keys/public.pem
echo "RSA keys generated in data/keys/"
