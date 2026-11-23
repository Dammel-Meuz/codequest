FROM node:24-alpine

COPY runner.cjs /runner.cjs

USER 10001:10001

ENTRYPOINT ["node", "/runner.cjs"]
