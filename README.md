<span style="font-size: 16px"><span style="color: green">Инициализация проекта (выполнять на хосте):</span><br> ./first-run.sh</span>

<span style="font-size: 16px"><span style="color: green">Запуск тестов + phpStan (выполнять на хосте):</span><br> ./check-full-project-from-docker.sh</span>

<span style="font-size: 16px"><span style="color: green">Запуск тестов (выполнять в контейнере app):</span><br> composer test</span>

<span style="font-size: 16px"><span style="color: green">Запуск cs-fixer fix (выполнять в контейнере app):</span><br> composer cs-fixer </span>

<span style="font-size: 16px"><span style="color: green">Запуск консольных команд (выполнять в контейнере app):</span><br> composer console -- 'имя команды'</span>

<span style="font-size: 16px"><span style="color: green">Запуск миграций (выполнять в контейнере app):</span><br> composer console -- migrations:migrate </span>
