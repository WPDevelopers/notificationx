#!/bin/sh

# Author : Priyo Mukul
# Copyright (c) WPDeveloper.net

echo "> Running NPM INSTALL Silently" && npm install -s && echo "> Running NPM BUILD for NotificationX Scripts" && gulp build && echo "." && echo "." && echo "." && echo "Making Build ( ZIP ) for WordPress.org" && wp dist-archive ../notificationx ../notificationx --allow-root && echo "√ Please check your wp-content folder.";