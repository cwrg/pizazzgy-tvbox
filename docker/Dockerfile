FROM php:7.4.33-fpm-alpine
ENV TZ=Asia/Shanghai

RUN sed -i "s/dl-cdn.alpinelinux.org/mirrors.ustc.edu.cn/g" /etc/apk/repositories

RUN apk --no-cache add tzdata \
    && cp "/usr/share/zoneinfo/$TZ" /etc/localtime \
    && echo "$TZ" > /etc/timezone

RUN apk --no-cache add nginx make g++ libpng libpng-dev curl-dev libzip-dev

RUN docker-php-ext-install curl opcache zip

RUN echo "#!/bin/sh" > /sleep.sh \
    && echo "while true" >> /sleep.sh \
    && echo "do" >> /sleep.sh \
    && echo "  cd /www && php upgrade.php" >> /sleep.sh \
    && echo "  sleep 60" >> /sleep.sh \
    && echo "done" >> /sleep.sh \
    && chmod +x /sleep.sh;

RUN echo "#!/bin/sh" > /run.sh \
    && echo "php-fpm -D" >> /run.sh \
    && echo "sh /sleep.sh > /dev/null 2>& 1 &" >> /run.sh \
    && echo "nginx -g 'daemon off;'" >> /run.sh \
    && chmod +x /run.sh

CMD ["sh","/run.sh"]

RUN apk --no-cache add shadow && usermod -u 1000 www-data && groupmod -g 1000 www-data

WORKDIR /www



