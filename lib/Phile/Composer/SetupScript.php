<?php
/**
 * Created by PhpStorm.
 * User: Keith
 * Date: 7/6/2016
 * Time: 9:52 AM
 */

namespace Phile\Composer;

use Composer\Composer;
use Composer\IO\IOInterface;
use Composer\Script\Event;
use Phile\Core\Utility;

class SetupScript {
    private $io;
    private $composer;

    public function __construct(IOInterface $io, Composer $composer){
        $this->io = $io;
        $this->composer = $composer;
    }

    public static function run(Event $event){
        $script = new self($event->getIO(), $event->getComposer());
        $script->execute();
    }

    private function execute(){
        $this->io->write('Welcome to PhileCMS');
        $this->io->write('To continue we first need to get a few configuration details out of the way.');

        $config = [
            'site_title' => $this->askSiteTitle()
            , 'encryptionKey' => $this->askEncryptionKey()
        ];

        $this->writeConfiguration($config);
        $this->createVarDirectory();
    }

    private function askSiteTitle(){
        $title = 'PhileCMS';
        $question = sprintf('<question>Site Title [%s]:</question> ', $title);

        return $this->io->ask($question, $title);
    }

    private function askEncryptionKey(){
        $key = $this->generateRandomKey();
        $question = sprintf('<question>Encryption Key [%s]:</question>', '<random>');

        return $this->io->ask($question, $key);
    }

    private function generateRandomKey(){
        return Utility::generateSecureToken(64);
    }

    private function getPath($sub){
        $rootDir = $this->composer->getPackage()->getTargetDir();

        return str_replace('/', DIRECTORY_SEPARATOR, $rootDir . '/'. $sub);
    }

    private function writeConfiguration(array $config){
        $contents = '<?php return ' . var_export($config, true) . ';';

        $configFile = $this->getPath('config.php');
        $fp = fopen($configFile, 'w');
        if (!$fp){
            $this->io->write('<error>Could not open configuration file (' . $configFile . ') for writing.</error>');
            $this->io->write('Please create this file with the following contents: ');
            $this->io->write($contents);
        } else {
            fwrite($fp, $contents);
            fclose($fp);
        }
    }

    private function createVarDirectory(){
        $cacheDir = $this->getPath('var/cache');
        $storageDir = $this->getPath('var/datastorage');

        $result = true;
        if (!file_exists($cacheDir)){
            $result = mkdir($cacheDir, 0775, true);
        }

        if (!file_exists($storageDir)){
            $result = $result && mkdir($storageDir, 0775, true);
        }

        if (!$result){
            $this->io->write('<error>Could not create cache and storage directories.</error>');
            $this->io->write('Please create the following directories and ensure they are writable by the server.');
            $this->io->write('  ' . $cacheDir);
            $this->io->write('  ' . $storageDir);
        }
    }
}
