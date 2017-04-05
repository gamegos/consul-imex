# Consul Imex (Import/Export) Tool

Consul-Imex is a simple import/export tool for [Consul](https://www.consul.io/) key/value storage over Consul HTTP API.

* Export all the keys in a prefix, save it as a json file.
* Import the keys from a json file to Consul under a specified prefix.
* Copy keys:
  - from a prefix to another prefix.
  - from a server to another server under a specified prefix.

## Requirements

* PHP >= 5.5.9, [Git](https://git-scm.com/) and [Composer](https://getcomposer.org/) are required to run Consul Imex in a PHP project.
* [Docker](https://www.docker.com/) is required to run Consul Imex in a docker container.

## Installation

### Install as a Composer dependency

    composer require gamegos/consul-imex

### Install as a PHP project

    composer create-project gamegos/consul-imex

Or clone/download and install manually:

    $ git clone https://github.com/gamegos/consul-imex.git
    $ cd consul-imex
    $ composer install


### Install as a docker image

    docker pull sozpinar/consul-imex

## Usage

### Export

Use one of the commands below depending on your installation:

* `php scripts/consul-imex.php export [options] <file>`
* `composer exec -- consul-imex export [options] <file>`
* `docker run -t sozpinar/consul-imex export [options] <file>`

#### Arguments:

* **file:** Output data file.

#### Options:

* **--url (-u):** Consul server url.
* **--prefix (-p):** Path prefix.


### Import

Use one of the commands below depending on your installation:

* `php scripts/consul-imex.php import [options] <file>`
* `composer exec -- consul-imex import [options] <file>`
* `docker run -t sozpinar/consul-imex import [options] <file>`

#### Arguments:

* **file:** Input data file.

#### Options:

* **--url (-u):** Consul server url.
* **--prefix (-p):** Path prefix.

### Copy

Use one of the commands below depending on your installation:

* `php scripts/consul-imex.php copy <source> <target>`
* `composer exec -- consul-imex copy <source> <target>`
* `docker run -t sozpinar/consul-imex copy <source> <target>`

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

    $ php scripts/consul-imex.php export -u http://localhost:8500 -p /old/prefix my-data.json
    93 keys are fetched.

### Import

    $ php scripts/consul-imex.php export -u http://localhost:8500 -p /new/prefix my-data.json
    93 keys are stored. (25 new directories are created.)

### Copy

* Copy keys from `/old/prefix` to `/new/prefix`:
```sh
$ php scripts/consul-imex.php copy -s http://localhost:8500 -t /old/prefix /new/prefix
93 keys are fetched.
93 keys are stored. (25 new directories are created.)
Operation completed.
```
* Copy keys under `/my/prefix` to another server:
```sh
$ php scripts/consul-imex.php copy -s http://localhost:8500 -t http://anotherhost:8500 /my/prefix /my/prefix
93 keys are fetched.
93 keys are stored. (25 new directories are created.)
Operation completed.
```
* Copy all keys to another server:
```sh
$ php scripts/consul-imex.php copy -s http://localhost:8500 -t http://anotherhost:8500
492 keys are fetched.
492 keys are stored. (108 new directories are created.)
Operation completed.
```

## Notice for Docker Usage

If your Consul service is in a private network or does not have a public URL, you may have to set up a custom network configuration for the docker container.

### Example

    $ docker run -it --net=host sozpinar/consul-imex export -u http://localhost:8500 -p /foo/bar
    93 keys are fetched.

If the default docker network type is `bridge` then the running container does not recognize 'localhost'. So we simply
add `--net=host` argument to make the container to use the network of the host machine.
