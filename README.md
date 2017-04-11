# Consul Imex (Import/Export) Tool

Consul-Imex is a simple import/export tool for [Consul](https://www.consul.io/) key/value storage over Consul HTTP API.

* Export all the keys in a prefix, save it as a json file.
* Import the keys from a json file to Consul under a specified prefix.
* Copy keys:
  - from a prefix to another prefix.
  - from a server to another server under a specified prefix.

## Installation

You can install Consul Imex in several ways:

* Executable phar file (requires PHP >=5.5.9)
* Docker image (requires [Docker](https://www.docker.com/) engine)
* Composer dependancy (requires [Git](https://git-scm.com/), [Composer](https://getcomposer.org/) and PHP >=5.5.9)
* Single PHP project (requires [Git](https://git-scm.com/), [Composer](https://getcomposer.org/) and PHP >=5.5.9)

### Install as an executable phar file

Download the phar file from `https://github.com/gamegos/consul-imex/releases/download/0.2.0/consul-imex.phar`, then assign execute permission for it. 

Example:

    wget -O /usr/local/bin/consul-imex https://github.com/gamegos/consul-imex/releases/download/0.2.0/consul-imex.phar
    chmod +x /usr/local/bin/consul-imex

### Install as a docker image

    docker pull sozpinar/consul-imex

### Install as a Composer dependency

    composer require gamegos/consul-imex

### Install as a PHP project

Install via `composer`:

    composer create-project gamegos/consul-imex

Or clone/download and install manually:

    git clone https://github.com/gamegos/consul-imex.git
    cd consul-imex
    composer install

## Usage

#### Run as an executable phar file:

    consul-imex <command> [options] [arguments]

#### Run as a docker container:

    docker run -t sozpinar/consul-imex <command> [options] [arguments]

#### Run as a composer vendor binary:

    composer exec -- consul-imex <command> [options] [arguments]

#### Run as a PHP script:

    php scripts/consul-imex.php <command> [options] [arguments]


### Export

#### Usage:

    consul-imex export [options] <file>

#### Arguments:

* **file:** Output data file.

#### Options:

* **--url (-u):** Consul server url.
* **--prefix (-p):** Path prefix.


### Import

#### Usage:

    consul-imex import [options] <file>

#### Arguments:

* **file:** Input data file.

#### Options:

* **--url (-u):** Consul server url.
* **--prefix (-p):** Path prefix.

### Copy

#### Usage:

    consul-imex copy [options] <source> <target>

#### Arguments:

* **source:** Source prefix.
* **target:** Target prefix.

#### Options:

* **--source-server (-s):** Source server URL.
* **--target-server (-t):** Target server URL. If omitted, source server is used as target server.

## Known Issues

### Directories with values

Consul key/value storage allows a directory to have a value like an ordinary key.
If a directory has a value, Consul Imex will ignore the value or the sub-keys;
this depends how the keys are ordered.

## Examples

### Export

    $ consul-imex export -u http://localhost:8500 -p /old/prefix my-data.json
    93 keys are fetched.

### Import

    $ consul-imex export -u http://localhost:8500 -p /new/prefix my-data.json
    93 keys are stored. (25 new directories are created.)

### Copy

Copy keys from `/old/prefix` to `/new/prefix`:

    $ consul-imex copy -s http://localhost:8500 -t /old/prefix /new/prefix
    93 keys are fetched.
    93 keys are stored. (25 new directories are created.)
    Operation completed.


Copy keys under `/my/prefix` to another server:

    $ consul-imex copy -s http://localhost:8500 -t http://anotherhost:8500 /my/prefix /my/prefix
    93 keys are fetched.
    93 keys are stored. (25 new directories are created.)
    Operation completed.


Copy all keys to another server:

    $ consul-imex copy -s http://localhost:8500 -t http://anotherhost:8500
    492 keys are fetched.
    492 keys are stored. (108 new directories are created.)
    Operation completed.


## Notice for Docker Usage

If your Consul service is in a private network or does not have a public URL, you may have to set up a custom network configuration for the docker container.

### Example

    $ docker run -it --net=host sozpinar/consul-imex export -u http://localhost:8500 -p /foo/bar
    93 keys are fetched.

If the default docker network type is `bridge` then the running container does not recognize 'localhost'. So we simply add `--net=host` argument to make the container to use the network of the host machine.
