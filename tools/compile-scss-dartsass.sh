#!/bin/sh -e

sass --style=compressed --no-source-map --watch scss/style.scss:static/css/style.css
