<span style="font-size: 16px"><span style="color: green">Инициализация проекта (выполнять на хосте):</span><br> ./first-run.sh</span>

<span style="font-size: 16px"><span style="color: green">Запуск тестов + phpStan (выполнять на хосте):</span><br> ./check-full-project-from-docker.sh</span>

<span style="font-size: 16px"><span style="color: green">Запуск тестов (<span style="color: orange">выполнять в контейнере app</span>):</span><br> composer test</span>

<span style="font-size: 16px"><span style="color: green">Запуск cs-fixer fix (<span style="color: orange">выполнять в контейнере app</span>):</span><br> composer cs-fixer </span>

<span style="font-size: 16px"><span style="color: green">Запуск консольных команд (<span style="color: orange">выполнять в контейнере app</span>):</span><br> composer console -- 'имя команды'</span>

<span style="font-size: 16px"><span style="color: green">Запуск миграций (<span style="color: orange">выполнять в контейнере app</span>):</span><br> composer console -- migrations:migrate </span>
