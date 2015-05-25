<?php

namespace tests;

use tests\models\User;
use Yii;
use yii\web\UploadedFile;

class UploadImageBehaviorTest extends DatabaseTestCase
{
    public function testFindUsers()
    {
        $data = User::find()->asArray()->all();
        $this->assertEquals(require(__DIR__ . '/data/test-find-users.php'), $data);
    }

    public function testFindUser()
    {
        $user = User::findOne(1);
        $this->assertEquals('admin', $user->nickname);
        $this->assertEquals('image-1.jpg', $user->image);
    }

    public function testGetFileInstance()
    {
        $file = UploadedFile::getInstanceByName('User[image]');
        $this->assertTrue($file instanceof UploadedFile);
    }

    public function testCreateUser()
    {
        $user = new User([
            'nickname' => 'Alex',
        ]);
        $user->setScenario('insert');

        $this->assertTrue($user->save());

        $path = $user->getUploadPath('image');
        $this->assertTrue(is_file($path));
        $this->assertEquals(sha1_file($path), sha1_file(__DIR__ . '/data/test-image.jpg'));
    }

    public function testResizeUser()
    {
        $user = User::findOne(1);
        $user->setScenario('update');

        $this->assertTrue($user->save());

        $thumbPath = $user->getThumbUploadPath('image', 'thumb');

        $thumbInfo = getimagesize($thumbPath);
        $this->assertEquals(400, $thumbInfo[0]);
        $this->assertEquals(300, $thumbInfo[1]);

        $previewPath = $user->getThumbUploadPath('image', 'preview');
        $previewInfo = getimagesize($previewPath);
        $this->assertEquals(200, $previewInfo[0]);
        $this->assertEquals(200, $previewInfo[1]);
    }

    /**
     * @inheritdoc
     */
    public static function setUpBeforeClass()
    {
        parent::setUpBeforeClass();

        $_FILES = [
            'User[image]' => [
                'name' => 'test-image.jpg',
                'type' => 'image/jpeg',
                'size' => 74463,
                'tmp_name' => __DIR__ . '/data/test-image.jpg',
                'error' => 0,
            ],
        ];
    }
}
