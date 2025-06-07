let
  nixpkgsVersion = {
    url = "https://github.com/NixOS/nixpkgs/archive/e318dabd6397aee8ef35f656c929762ccc373c86.tar.gz";
    sha256 = "0ianlsjsa6bhqsa9y3s0agd6kcs3wv532rhar63xxxdwjafk2krf";
  };

  pinnedPkgs = import (fetchTarball nixpkgsVersion) { };
  in pinnedPkgs.mkShell {
    buildInputs = with pinnedPkgs; [
      (php84.withExtensions ({ all, ... }:
        with all; [
          ctype
          curl
          dom
          filter
          gd
          intl
          mbstring
          opcache
          openssl
          pdo
          pdo_mysql
          simplexml
          tokenizer
          xmlwriter
          zlib
        ]))
      _7zz
      curl
      file
      gifsicle
      hadolint
      icu
      jpeginfo
      jpegoptim
      jq
      libwebp
      libxml2
      mdbtools
      nodejs
      oxipng
      pngcheck
      pngquant
      shellcheck
      shfmt
      temurin-jre-bin
      unzip
      yamllint
    ];

    shellHook = ''
      if [ ! -d "./node_modules" ]; then
        echo "Installing dependencies..."
        npm ci
      else
        echo "Dependencies already installed. Delete node_modules directory to reinstall or update them."
      fi
    '';
  }
