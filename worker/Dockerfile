FROM node:20-slim

WORKDIR /worker

COPY package*.json ./
RUN npm install --omit=dev

COPY . .

CMD ["tail", "-f", "/dev/null"]
