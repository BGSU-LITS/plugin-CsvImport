<?php
class CsvImport_FileIngestUrl extends Omeka_File_Ingest_Url
{
    protected function _getOriginalFilename($fileInfo)
    {
        $original = Omeka_File_Ingest_AbstractSourceIngest::
            _getOriginalFilename($fileInfo);

        if (!$original) {
            try {
                $client = $this->_getHttpClient($fileInfo['source']);
                $client->setHeaders('Accept-encoding', 'identity');
                $response = $client->request('HEAD');

                if ($response->isSuccessful()) {
                    $content = $response->getHeader('Content-Disposition');

                    if ($filename = self::_parseFilename($content)) {
                        $original = $filename;
                    }
                }
            } catch (Zend_Http_Client_Exception $e) {
            }
        }

        if (!$original) {
            $original = parent::_getOriginalFilename($fileInfo);
        }

        return $original;
    }

    protected static function _parseFilename($content)
    {
        if (is_array($content)) {
            $content = reset($content);
        }

        if (is_string($content) && trim($content) !== '') {
            foreach (explode(';', $content) as $part) {
                list($key, $value) = preg_split('/\s*=\s*/', trim($part), 2);

                if (empty($key) || empty($value)) {
                    continue;
                }

                if (substr($key, -1) === '*') {
                    $key = substr($key, 0, -1);
                    $pattern = "/([\w!#$%&+^_`{}~-]+)'([\w-]*)'(.*)$/";

                    if (preg_match($pattern, $value, $matches)) {
                        $value = mb_convert_encoding(
                            rawurldecode($matches[3]),
                            'utf-8',
                            $matches[1]
                        );
                    }
                }

                if ($key === 'filename') {
                    return trim($value, " \n\r\t\v\0\"");
                }
            }
        }

        return false;
    }
}