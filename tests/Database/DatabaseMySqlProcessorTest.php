<?php

class DatabaseMySqlProcessorTest extends PHPUnit_Framework_TestCase
{
    public function testProcessColumnListing()
    {
        $processor = new Illuminate\Database\Query\Processors\MySqlProcessor();
        $listing = [['column_name' => 'id'], ['column_name' => 'name'], ['column_name' => 'email']];
        $expected = ['id', 'name', 'email'];
        $this->assertEquals($expected, $processor->processColumnListing($listing));

        // convert listing to objects to simulate PDO::FETCH_CLASS
        foreach ($listing as &$row) {
            $row = (object) $row;
        }

        $this->assertEquals($expected, $processor->processColumnListing($listing));
    }
}
