<?php namespace Tests\Browser\Helpers;

use Tests\TestCase;

class ChromeTestCase extends TestCase
{
    public $headless = false;

    /**
     * @var Browser
     */
    protected $browser;

    public function setUp()
    {
        $this->browser = new Browser($this);
        $this->isolateInTransaction = false;
        parent::setUp();
        $this->browser->setUp();
        $this->backupSchema();
    }

    protected function tearDown()
    {
        $this->restoreSchema();
        parent::tearDown();
        $this->isolateInTransaction = true;
        $this->browser->tearDown();
    }

    public function backupSchema()
    {
    }

    public function restoreSchema()
    {
        $tables = \DB::connection()->getDoctrineSchemaManager()->listTableNames();
        foreach ($tables as $table) {
            if ($table !== 'countries' && $table  !== 'migrations') {
                // SQLi: the tables names come from PostgreSQL, so there shouldn't be a injection possible here
                // and we don't have a method to escape table name in PDO

                if (count(\DB::selectOne("SELECT COUNT(1) FROM $table")) > 0) {
                    dump("Truncate $table\n");
                    \DB::unprepared("TRUNCATE $table");
                }
            }
        };
    }

}