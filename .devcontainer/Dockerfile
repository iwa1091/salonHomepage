FROM mcr.microsoft.com/devcontainers/php:1-8.3-bookworm

# Node.js 20
RUN curl -fsSL https://deb.nodesource.com/setup_20.x | bash - \
  && apt-get update \
  && apt-get install -y nodejs \
  && npm -v && node -v \
  && apt-get clean && rm -rf /var/lib/apt/lists/*
