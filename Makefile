SW_API_HOSTNAME ?= api.synergywholesale.com
RELEASE_DATE := $(shell date '+%A, %B %d %Y')

# Make sure sed replace works on Mac OSX
SED_PARAM := 
ifeq ($(shell uname -s),Darwin)
	SED_PARAM += ''
endif

# In case the version tag isn't annoated, let's have a fallback
VERSION := $(shell git describe --abbrev=0)
ifneq ($(.SHELLSTATUS), 0)
	VERSION := $(shell git describe --tags)
endif

VERSION := $(firstword $(subst -, ,${VERSION}))

replace:
	sed -i${SED_PARAM} "s/{{VERSION}}/${VERSION}/g" "README.txt"
	sed -i${SED_PARAM} "s/{{RELEASE_DATE}}/${RELEASE_DATE}/g" "README.txt"
	# sed -i${SED_PARAM} "s/{{VERSION}}/${VERSION:v%=%}/g" "modules/servers/synergywholesale_ssl/synergywholesale_ssl.php"
	# sed -i${SED_PARAM} "s/{{API}}/${SW_API_HOSTNAME}/g" "modules/servers/synergywholesale_ssl/synergywholesale_ssl.php"

revert:
	sed -i${SED_PARAM} "s/${VERSION}/{{VERSION}}/g" "README.txt"
	sed -i${SED_PARAM} "s/${RELEASE_DATE}/{{RELEASE_DATE}}/g" "README.txt"
	# sed -i${SED_PARAM} "s/${VERSION:v%=%}/{{VERSION}}/g" "modules/servers/synergywholesale_ssl/synergywholesale_ssl.php"
	# sed -i${SED_PARAM} "s/${SW_API_HOSTNAME}/{{API}}/g" "modules/servers/synergywholesale_ssl/synergywholesale_ssl.php"
	
package:
	make replace
	zip -r "synergy-wholesale-ssl-$(VERSION).zip" . -x  \
	'.DS_Store' '**/.DS_Store' '*.cache' '.git*' '*.md' 'Makefile' 'package.json' 'package-lock.json' \
	'composer.json' 'composer.lock' '*.xml' '**/functions.js' \
	'vendor/*' 'node_modules/*' '.git/*' 'tests/*'
	make revert

build:
	make test
	make replace
	make package
	make revert

test:
	test -s vendor/bin/phpcs || composer install
	./vendor/bin/phpcs
	./vendor/bin/phpunit
	
tools:
	composer install