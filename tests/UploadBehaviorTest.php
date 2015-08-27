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
        $data = Document::find()->asArray()->all();
        $this->assertEquals(require(__DIR__ . '/data/test-find-documents.php'), $data);
    }

    public function testFindDocument()
    {
        $document = Document::findOne(3);
        $this->assertEquals('Doc 3', $document->title);
        $this->assertEquals('file-3.jpg', $document->file);
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

    public function testSetInstanceManual()
    {
        $document = new Document([
            'title' => 'Doc 4',
        ]);
        $document->file = UploadedFile::getInstanceByName('Document[file-other]');
        $document->setScenario('insert');

        $this->assertTrue($document->save());

        $path = $document->getUploadPath('file');
        $this->assertTrue(is_file($path));
        $this->assertEquals(sha1_file($path), sha1_file(__DIR__ . '/data/test-file-other.txt'));
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
            'Document[file-other]' => [
                'name' => 'test-file-other.txt',
                'type' => 'text/plain',
                'size' => 12,
                'tmp_name' => __DIR__ . '/data/test-file-other.txt',
                'error' => 0,
            ],
        ];
    }
}
