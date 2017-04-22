<?php

namespace MusicBrainz\Tests;

use MusicBrainz\HttpAdapters\AbstractHttpAdapter;
use MusicBrainz\HttpAdapters\GuzzleFiveAdapter;
use MusicBrainz;

/**
 * @covers MusicBrainz\MusicBrainz
 */
class MusicBrainzTest extends \PHPUnit_Framework_TestCase
{
    const USERNAME = 'testuser';
    const PASSWORD = 'testpass';
    /**
     * @var \MusicBrainz\MusicBrainz
     */
    protected $brainz;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $httpAdapter;

    public function setUp()
    {
        $this->httpAdapter = $httpAdapter = $this->createMock(AbstractHttpAdapter::class);

        $this->brainz = new MusicBrainz\MusicBrainz($httpAdapter, self::USERNAME, self::PASSWORD );
    }

    /**
     * @return array
     */
    public function MBIDProvider()
    {
        return array(
            array(true, '4dbf5678-7a31-406a-abbe-232f8ac2cd63'),
            array(true, '4dbf5678-7a31-406a-abbe-232f8ac2cd63'),
            array(false, '4dbf5678-7a314-06aabb-e232f-8ac2cd63'), // invalid spacing for UUID's
            array(false, '4dbf5678-7a31-406a-abbe-232f8az2cd63') // z is an invalid character
        );
    }

    /**
     * @dataProvider MBIDProvider
     */
    public function testIsValidMBID($validation, $mbid)
    {
        $this->assertEquals($validation, $this->brainz->isValidMBID($mbid));
    }

    public function testHttpOptions()
    {
        $applicationName = 'php-musibrainz';
        $version = '1.0.0';
        $contactInfo = 'development@oguzhanuysal.eu';

        $this->brainz->setUserAgent($applicationName, $version, $contactInfo);

        $userAgent = $applicationName . '/' . $version . ' (' . $contactInfo . ')';

        $httpOptionsExpect = [
            'method'        => 'GET',
            'user-agent'    => $userAgent,
            'user'          => self::USERNAME,
            'password'      => self::PASSWORD
        ];

        $this->assertEquals($httpOptionsExpect, $this->brainz->getHttpOptions());
        $this->assertEquals($userAgent, $this->brainz->getUserAgent());
    }

    public function testGetSetters()
    {
        $this->assertEquals(self::USERNAME, $this->brainz->getUser());
        $this->assertEquals(self::PASSWORD, $this->brainz->getPassword());
    }

    /**
     * @test
     */
    public function willValidateFilter()
    {
        $this->assertTrue($this->brainz->validateFilter(['official'], MusicBrainz\MusicBrainz::$validReleaseStatuses));
    }

    /**
     * @test
     * @expectedException MusicBrainz\Exception
     */
    public function willThrowExceptionIfFilterValidationFails()
    {
        $this->brainz->validateFilter(['Invalid'], MusicBrainz\MusicBrainz::$validReleaseTypes);
    }

    /**
     * @test
     */
    public function willValidateInclude()
    {
        $includes = array(
            'releases',
            'recordings',
            'release-groups',
            'user-ratings'
        );

        $this->assertTrue($this->brainz->validateInclude($includes, MusicBrainz\MusicBrainz::$validIncludes['artist']));
    }

    /**
     * @test
     * @expectedException \OutOfBoundsException
     */
    public function willThrowOutOfBoundsExceptionIfIncludeValidationFails()
    {
        $this->brainz->validateInclude(['out-of-bound'], MusicBrainz\MusicBrainz::$validIncludes['artist']);
    }

    /**
     * @test
     * @expectedException MusicBRainz\Exception
     */
    public function userAgentVersionCannotContainDash()
    {
        $this->brainz->setUserAgent('application', '1.0-beta', 'test');
    }

    public function testLookup()
    {
        $includes = array(
            'releases',
            'recordings',
            'release-groups',
            'user-ratings'
        );

        $this->httpAdapter->expects($this->once())
            ->method('call')
            ->willReturn('{"secondary-type-ids":["dd2a21e1-0c00-3729-a7a0-de60b84eb5d1","0c60f497-ff81-3818-befd-abfc84a4858b"],"id":"e4307c5f-1959-4163-b4b1-ded4f9d786b0","title":"Born This Way: The Remix","secondary-types":["Compilation","Remix"],"disambiguation":"","firs
t-release-date":"2011-11-18","primary-type-id":"f529b476-6e62-324f-b0aa-1f3e33d313fc","primary-type":"Album"}');

        $this->brainz->lookup('artist', '4dbf5678-7a31-406a-abbe-232f8ac2cd63', $includes);
    }
}
