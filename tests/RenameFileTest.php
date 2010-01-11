<?php
require_once 'SingleFileTest.php';

/**
 * @runTestsInSeparateProcesses
 */
class RenameFileTest extends Scisr_SingleFileTest
{

    public function renameAndCompare($original, $expected, $oldname='Foo.php', $newname='Baz.php', $aggressive=false) {
        $this->populateFile($original);

        $s = new Scisr();
        if ($aggressive) {
            $s->setEditMode(Scisr::MODE_AGGRESSIVE);
        }
        $s->setRenameFile($oldname, $newname);
        $s->addFile($this->test_file);
        $s->run();

        $this->compareFile($expected);

    }

    /**
     * @dataProvider includeProvider
     */
    public function testSimpleRename($orig) {
        $orig = "<?php\n$orig";
        $expected = str_replace('Foo.php', 'Baz.php', $orig);
        $this->renameAndCompare($orig, $expected);
    }

    public function includeProvider() {
        return array(
            array('require("Foo.php");'),
            array('include("Foo.php");'),
            array('require_once("Foo.php");'),
            array('include_once("Foo.php");'),
            array('require_once "Foo.php";'),
            array("require_once('Foo.php');"),
            array('require_once(    "Foo.php"   );'),
        );
    }

    /**
     * @dataProvider partialMatchProvider
     */
    public function testDontRenamePartialMatches($orig) {
        $orig = "<?php\n$orig";
        $this->renameAndCompare($orig, $orig);
    }

    public function partialMatchProvider() {
        return array(
            array('require_once("Foo.php.old");'),
            array('require_once("Foo.php/actualfile");'),
            array('require_once("notmyfolder/Foo.php");'),
            array('require_once("Foo.php" . ".old");'),
            array('require_once("notmyfolder/" . "Foo.php");'),
            array('require_once(SOME_CONSTANT . "Foo.php");'),
            array('require_once(function_call(2) . "Foo.php");'),
            array('require_once(function_call("Foo.php"));'),
        );
    }

    /**
     * @dataProvider partialPathProvider
     * @todo this test depends on special knowledge of the folder structure of 
     * our "single file" in Scisr_SingleFileTest which we really shouldn't have
     * any control over. Let's fix this.
     */
    public function testPartialPathRename($dir, $oldCode, $oldArg, $newCode, $newArg) {
        $baseDir = dirname(__FILE__);

        chdir("$baseDir/$dir");
        $oldCode = "<?php\nrequire_once('$oldCode');";
        $newCode = "<?php\nrequire_once('$newCode');";
        $this->renameAndCompare($oldCode, $newCode, $oldArg, $newArg);

    }

    public function partialPathProvider() {
        return array(
            array('d1/d2', 'Foo.php', 'Foo.php', 'Bar.php', 'Bar.php'),
            array('d1/d2', 'd1/d2/Foo.php', 'Foo.php', 'd1/d2/Bar.php', 'Bar.php'),
            array('d1', 'd1/d2/Foo.php', 'd2/Foo.php', 'd1/d2/Bar.php', 'd2/Bar.php'),
            array('d1', 'd1/d2/Foo.php', 'd2/Foo.php', 'd1/Bar.php', 'Bar.php'),
            array('', 'Foo.php', 'd1/d2/Foo.php', 'Bar.php', 'd1/d2/Bar.php'),
            array('', 'd2/Foo.php', 'd1/d2/Foo.php', 'd2/Bar.php', 'd1/d2/Bar.php'),
            array('', 'Foo.php', 'd1/d2/Foo.php', 'd3/Bar.php', 'd1/d2/d3/Bar.php'),
        );
    }

    /**
     * @dataProvider relativePathProvider
     */
    public function testPathRelativeTo($path, $base, $expected) {
        $o = new Scisr_Operations_ChangeFile('dummy', 'dummy');
        $this->assertSame($expected, $o->pathRelativeTo($path, $base));
    }

    public function relativePathProvider() {
        return array(
            array('/home/user/foo.php', '/home/user/', 'foo.php'),
            array('/home/user/foo.php', '/home/user', 'foo.php'),
            array('/home/user/foo.php', '', '/home/user/foo.php'),
            array('/home/user/foo.php', '/', 'home/user/foo.php'),
        );
    }

    /**
     * @dataProvider matchPathsProvider
     */
    public function testMatchPaths($path, $newPath, $expected) {
        $phpcsfile = $this->getMock('PHP_CodeSniffer_File', null, array(), '', false);
        $o = new Scisr_Operations_ChangeFile('dummy', 'dummy');
        $this->assertSame($expected, $o->matchPaths($path, $newPath, $phpcsfile));
    }

    public function matchPathsProvider() {
        return array(
            array('/home/user/foo.php', 'foo.php', '/home/user/'),
            array('/home/user/foo.php', 'user/foo.php', '/home/'),
            array('/home/user/foo.php', '/home/user/foo.php', ''),
            array('/home/user/foo.php', 'home/user/foo.php', '/'),
            array('/home/user/foo.php', '/home/admin/foo.php', false),
            array('/home/user/foo.php', '/user/foo.php', false),
        );
    }

}
