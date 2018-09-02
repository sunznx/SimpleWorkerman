<?php

namespace Sunznx\SimpleWorkerman\RPC\Model;

class User
{
    public function info($user_id)
    {
        return [
            'id' => $user_id,
            'name' => 'john',
            'age' => 13,
            'gender' => 0
        ];
    }

    public function list()
    {
        $user1 = [
            'id' => 1,
            'name' => 'user1'
        ];
        $user2 = [
            'id' => 2,
            'name' => 'user2'
        ];

        return [
            $user1,
            $user2
        ];
    }
}