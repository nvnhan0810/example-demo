FROM node:22-alpine
WORKDIR /app

COPY package.json package-lock.json* ./
RUN npm install

COPY tsconfig.json ./
COPY src ./src

ENV NODE_ENV=production
EXPOSE 3001
CMD ["node", "--import", "tsx", "src/main.ts"]

