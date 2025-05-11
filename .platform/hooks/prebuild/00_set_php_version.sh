#!/bin/bash
echo "Forcing PHP 7.4 install"
amazon-linux-extras enable php7.4
dnf clean metadata
dnf install -y php php-cli php-mbstring php-pdo php-xml php-mysqlnd php-gd
