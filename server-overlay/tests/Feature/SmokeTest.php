<?php

test('the application boots', function () {
    $response = $this->get('/up');

    $response->assertStatus(200);
});
