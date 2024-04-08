<?php

namespace dsarhoya\DSYFilesBundle\Interfaces;

/**
 * Descripcion Inferface que permite subir multiples archivos
 * Example:.
 *
 * public function getFiles(){
 *       return array(
 *          array(
 *              'file'=>$this->getFile1(),
 *              'fileKey'=>sprintf('img1_%s.%s', md5(time()), $this->file1->guessExtension()),
 *              'filePath'=>$this->getFile1Path(),
 *              'fileProperties'=>$this->getFile1Properties()
 *          ),
 *          array(
 *              'file'=>$this->getFile2(),
 *              'fileKey'=>sprintf('img2_%s.%s', md5(time()), $this->file2->guessExtension()),
 *              'filePath'=>$this->getFile2Path(),
 *              'fileProperties'=>$this->getFile2Properties()
 *          ),
 *       );
 *   }
 */
interface IMultipleFileEnabledEntity
{
    public function getFiles();
}
