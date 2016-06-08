<?php

class LintTest extends \PHPUnit_Framework_TestCase
{
    public function testSrc()
    {
        $fileList = glob('src/');

        $i = 0;

        while ( $inode = array_shift($fileList) ) {

            if ( is_dir($inode) ) {
                $fileList = array_merge($fileList, glob(realpath($inode).'/*'));
                continue;
            }

            if (preg_match('/^.+\.php$/i', $inode)) {
                $this->lintFile($inode);
            }

        }
    }

    private function lintFile($filename = '')
    {
        print('.');
        $this->assertContains('No syntax errors', exec(sprintf('php -l "%s"', $filename), $out), sprintf("%s contains syntax errors", $filename));
    }
}
