<?php

namespace tests;

use tests\models\Document;
use Yii;
use yii\web\UploadedFile;

/**
 * Class UploadBehaviorTest
 */
class UploadBehaviorTest extends DatabaseTestCase
{
    public function testFindDocuments()
    {
        $data = [];
        $posts = Document::find()->all();
        foreach ($posts as $post) {
            $data[] = $post->toArray();
        }
        $this->assertEquals(require(__DIR__ . '/data/test-find-documents.php'), $data);
    }

    public function testFindDocument()
    {
        $post = Document::findOne(3);
        $this->assertEquals('Doc 3', $post->title);
        $this->assertEquals('file-3.jpg', $post->file);
    }

    public function testGetFileInstance()
    {
        $file = UploadedFile::getInstanceByName('Document[file]');
        $this->assertTrue($file instanceof UploadedFile);
    }

    public function testSanitize()
    {
        $document = new Document();
        $this->assertEquals($document->sanitize('#my  unsaf&filename?".png'), '-my--unsaf-filename--.png');
    }

    public function testCreateDocument()
    {
        $document = new Document([
            'title' => 'Doc 4',
        ]);
        $document->setScenario('insert');

        $this->assertTrue($document->save());

        $path = $document->getUploadPath('file');
        $this->assertTrue(is_file($path));
        $this->assertEquals(sha1_file($path), sha1_file(__DIR__ . '/data/test-file.txt'));
    }

    /**
     * @inheritdoc
     */
    public static function setUpBeforeClass()
    {
        parent::setUpBeforeClass();

        $_FILES = [
            'Document[file]' => [
                'name' => 'test-file.txt',
                'type' => 'text/plain',
                'size' => 12,
                'tmp_name' => __DIR__ . '/data/test-file.txt',
                'error' => 0,
            ],
        ];
    }
}
