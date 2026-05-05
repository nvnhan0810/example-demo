FROM node:22-alpine AS base
WORKDIR /app

ARG SERVICE_NAME

COPY package.json package-lock.json tsconfig.json ./
COPY libs ./libs
COPY services ./services

RUN npm ci

ENV NODE_ENV=production
ENV SERVICE_NAME=$SERVICE_NAME

CMD ["sh", "-c", "node --import tsx services/$SERVICE_NAME/src/main.ts"]

