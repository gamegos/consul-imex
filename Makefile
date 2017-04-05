# Define internal variables.
package = sozpinar/consul-imex
git-tag = ${shell git describe}
changes = ${shell git status --porcelain | wc -l}

# Find an appropriate tag if the VERSION parameter is not set.
ifdef VERSION
	build-version = $(VERSION)
else ifeq ($(changes), 0)
	build-version = $(git-tag)
else
	build-version = $(git-tag)-dev
endif

build-image:
	docker build --rm -t $(package):$(build-version) --label type=$(package) .
	@ untagged=`docker images -f label=type=$(package) -f dangling=true -q`; \
	if [ ! "$$untagged" = "" ]; then \
		echo "Removing untagged '$(package)' images: $$untagged"; \
		docker rmi -f $$untagged; \
	fi

build-phar:
	docker run -it --rm -v "${shell pwd}":/app -w /app php:7.1-alpine \
		php -d phar.readonly=0 \
			scripts/build-phar.php -u ${shell id -u} -g ${shell id -g} $(build-version)
