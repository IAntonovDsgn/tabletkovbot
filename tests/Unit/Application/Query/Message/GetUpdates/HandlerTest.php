<?php

use App\Domain\Entities\Message\MessageServiceInterface;

test('', function (int $countMessages) {
    $messages = [];
    for ($i = 0; $i < $countMessages; $i++) {
        $messages[] = new \App\Domain\Entities\Message\MessageOutputDTO($i+1, 'test message');
    }

    $messageFacade = Mockery::mock(MessageServiceInterface::class);
    $messageFacade
        ->shouldReceive('getUpdates')
        ->andReturn($messages);

    expect($messageFacade->getUpdates())->toHaveCount($countMessages);

})->with('get updates dataset');

dataset('get updates dataset', [
    'Новых сообщений 0' => 0,
    'Новых сообщений 1' => 1,
    'Новых сообщений 2' => 2,
]);
