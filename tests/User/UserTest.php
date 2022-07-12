<?php


use App\Classess\Models\User;
use PHPUnit\Framework\TestCase;

class UserTest extends TestCase
{
private $user;

protected function setUp(): void
{
    $this->user = new User();
    $this->user->setAge(22);
}

protected function tearDown(): void
{

}

/**
 * @dataProvider userProvider
 */
public function testAge($age) {
    // 22 ==
    $this->assertEquals($age, $this->user->getAge());
}

public function userProvider() {
    return [
        'one' => [1],
        'correct' => [22]
    ];
}
}