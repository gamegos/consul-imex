# Consul Imex (Import/Export) Tool

Consul-Imex is a simple import/export tool for [Consul](https://www.consul.io/) key/value storage over Consul HTTP API.

* Export all the keys in a prefix, save it as a json file.
* Import the keys from a json file to Consul under a specified prefix.
* Copy keys:
  - from a prefix to another prefix.
  - from a server to another server under a specified prefix.

## Installation

### Install as Composer dependency

    composer require gamegos/consul-imex:dev-master

### Install as a project

    composer create-project gamegos/consul-imex:dev-master

## Usage

### Export

    Usage:
      php scripts/consul-imex.php export [options] <file>
      composer exec -- consul-imex export [options] <file>

    Arguments:
      file                  Output data file.

    Options:
      -u, --url[=URL]       Consul server url.
      -p, --prefix[=PREFIX] Path prefix.

### Import

    Usage:
      php scripts/consul-imex.php import [options] <file>
      composer exec -- consul-imex import [options] <file>

    Arguments:
      file                  Input data file.

    Options:
      -u, --url[=URL]       Consul server url.
      -p, --prefix[=PREFIX] Path prefix.

### Copy

    Usage:
      php scripts/consul-imex.php copy <source> <target>
      composer exec -- consul-imex copy <source> <target>

    Arguments:
      source                             Source prefix.
      target                             Target prefix.

    Options:
      -s, --source-server=SOURCE-SERVER  Source server URL.
      -t, --target-server=TARGET-SERVER  Target server URL. If omitted, source server is used as target server.

## Known Issues

### Directories with values

Consul key/value storage allows a directory to have a value like an ordinary key.
If a directory has a value, Consul Imex will ignore the value or the sub-keys;
this depends how the keys are ordered.

