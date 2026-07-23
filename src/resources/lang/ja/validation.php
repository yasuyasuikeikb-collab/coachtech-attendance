<?php

return [
    'required' => ':attributeを入力してください',
    'email' => ':attributeはメール形式で入力してください',
    'min' => [
        'string' => ':attributeは:min文字以上で入力してください',
    ],
    'confirmed' => ':attributeと一致しません',
    'unique' => 'この:attributeは既に登録されています',
    'max' => [
        'string' => ':attributeは:max文字以内で入力してください',
    ],
    'date_format' => ':attributeは時刻形式で入力してください',
    'after' => ':attributeは:dateより後にしてください',

    'attributes' => [
        'name' => 'お名前',
        'email' => 'メールアドレス',
        'password' => 'パスワード',
        'password_confirmation' => '確認用パスワード',
    ],
];