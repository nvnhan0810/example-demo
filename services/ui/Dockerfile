FROM node:22-alpine
WORKDIR /app

COPY package.json package-lock.json* ./
RUN npm install

COPY index.html vite.config.ts ./
COPY src ./src

ENV VITE_API_PROXY_TARGET=http://gateway:3000
EXPOSE 5173
CMD ["npm", "run", "dev"]

