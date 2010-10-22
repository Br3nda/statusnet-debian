<?php

if (isset($_SERVER) && array_key_exists('REQUEST_METHOD', $_SERVER)) {
    print "This script must be run from the command line\n";
    exit();
}

// XXX: we should probably have some common source for this stuff

define('INSTALLDIR', realpath(dirname(__FILE__) . '/..'));
define('STATUSNET', true);

require_once INSTALLDIR . '/lib/common.php';

class ActivityParseTests extends PHPUnit_Framework_TestCase
{
    public function testExample1()
    {
        global $_example1;
        $dom = DOMDocument::loadXML($_example1);
        $act = new Activity($dom->documentElement);

        $this->assertFalse(empty($act));

        $this->assertEquals(1243860840, $act->time);
        $this->assertEquals(ActivityVerb::POST, $act->verb);

        $this->assertFalse(empty($act->objects[0]));
        $this->assertEquals('Punctuation Changeset', $act->objects[0]->title);
        $this->assertEquals('http://versioncentral.example.org/activity/changeset', $act->objects[0]->type);
        $this->assertEquals('Fixing punctuation because it makes it more readable.', $act->objects[0]->summary);
        $this->assertEquals('tag:versioncentral.example.org,2009:/change/1643245', $act->objects[0]->id);
    }

    public function testExample2()
    {
        global $_example2;
        $dom = DOMDocument::loadXML($_example2);
        $act = new Activity($dom->documentElement);

        $this->assertFalse(empty($act));
        // Did we handle <content type="html"> correctly with a typical payload?
        $this->assertEquals("<p>Geraldine posted a Photo on PhotoPanic</p>\n     " .
                            "<img src=\"/geraldine/photo1.jpg\">", trim($act->content));
    }

    public function testExample3()
    {
        global $_example3;
        $dom = DOMDocument::loadXML($_example3);

        $feed = $dom->documentElement;

        $entries = $feed->getElementsByTagName('entry');

        $entry = $entries->item(0);

        $act = new Activity($entry, $feed);

        $this->assertFalse(empty($act));
        $this->assertEquals(1071340202, $act->time);
        $this->assertEquals('http://example.org/2003/12/13/atom03.html', $act->link);

        $this->assertEquals($act->verb, ActivityVerb::POST);

        $this->assertFalse(empty($act->actor));
        $this->assertEquals(ActivityObject::PERSON, $act->actor->type);
        $this->assertEquals('John Doe', $act->actor->title);
        $this->assertEquals('mailto:johndoe@example.com', $act->actor->id);

        $this->assertFalse(empty($act->objects[0]));
        $this->assertEquals(ActivityObject::NOTE, $act->objects[0]->type);
        $this->assertEquals('urn:uuid:1225c695-cfb8-4ebb-aaaa-80da344efa6a', $act->objects[0]->id);
        $this->assertEquals('Atom-Powered Robots Run Amok', $act->objects[0]->title);
        $this->assertEquals('Some text.', $act->objects[0]->summary);
        $this->assertEquals('http://example.org/2003/12/13/atom03.html', $act->objects[0]->link);

        $this->assertFalse(empty($act->context));

        $this->assertTrue(empty($act->target));

        $this->assertEquals($act->entry, $entry);
        $this->assertEquals($act->feed, $feed);
    }

    public function testExample4()
    {
        global $_example4;
        $dom = DOMDocument::loadXML($_example4);

        $entry = $dom->documentElement;

        $act = new Activity($entry);

        $this->assertFalse(empty($act));
        $this->assertEquals(1266547958, $act->time);
        $this->assertEquals('http://example.net/notice/14', $act->link);

        $this->assertFalse(empty($act->context));
        $this->assertEquals('http://example.net/notice/12', $act->context->replyToID);
        $this->assertEquals('http://example.net/notice/12', $act->context->replyToUrl);
        $this->assertEquals('http://example.net/conversation/11', $act->context->conversation);
        $this->assertEquals(array('http://example.net/user/1'), $act->context->attention);

        $this->assertFalse(empty($act->objects[0]));
        $this->assertEquals($act->objects[0]->content,
                            '@<span class="vcard"><a href="http://example.net/user/1" class="url"><span class="fn nickname">evan</span></a></span> now is the time for all good men to come to the aid of their country. #<span class="tag"><a href="http://example.net/tag/thetime" rel="tag">thetime</a></span>');

        $this->assertFalse(empty($act->actor));
    }

    public function testExample5()
    {
        global $_example5;
        $dom = DOMDocument::loadXML($_example5);

        $feed = $dom->documentElement;

        // @todo Test feed elements

        $entries = $feed->getElementsByTagName('entry');
        $entry = $entries->item(0);

        $act = new Activity($entry, $feed);

        // Post
        $this->assertEquals($act->verb, ActivityVerb::POST);
        $this->assertFalse(empty($act->context));

        // Actor w/Portable Contacts stuff
        $this->assertFalse(empty($act->actor));
        $this->assertEquals($act->actor->type, ActivityObject::PERSON);
        $this->assertEquals($act->actor->title, 'Test User');
        $this->assertEquals($act->actor->id, 'http://example.net/mysite/user/3');
        $this->assertEquals($act->actor->link, 'http://example.net/mysite/testuser');

        $avatars = $act->actor->avatarLinks;

        $this->assertEquals(
                $avatars[0]->url,
                'http://example.net/mysite/avatar/3-96-20100224004207.jpeg'
        );

        $this->assertEquals($act->actor->displayName, 'Test User');

        $poco = $act->actor->poco;
        $this->assertEquals($poco->preferredUsername, 'testuser');
        $this->assertEquals($poco->address->formatted, 'San Francisco, CA');
        $this->assertEquals($poco->urls[0]->type, 'homepage');
        $this->assertEquals($poco->urls[0]->value, 'http://example.com/blog.html');
        $this->assertEquals($poco->urls[0]->primary, 'true');
        $this->assertEquals($act->actor->geopoint, '37.7749295 -122.4194155');
    }

    public function testExample6()
    {
        global $_example6;

        $dom = DOMDocument::loadXML($_example6);

        $rss = $dom->documentElement;

        $channels = $dom->getElementsByTagName('channel');

        $channel = $channels->item(0);

        $items = $channel->getElementsByTagName('item');

        $item = $items->item(0);

        $act = new Activity($item, $channel);

        $this->assertEquals($act->verb, ActivityVerb::POST);

        $this->assertEquals($act->id, 'http://en.blog.wordpress.com/?p=3857');
        $this->assertEquals($act->link, 'http://en.blog.wordpress.com/2010/03/03/rub-a-dub-dub-in-the-pubsubhubbub/');
        $this->assertEquals($act->title, 'Rub-a-Dub-Dub in the PubSubHubbub');
        $this->assertEquals($act->time, 1267634892);

        $actor = $act->actor;

        $this->assertFalse(empty($actor));
        $this->assertEquals($actor->title, "Joseph Scott");
    }

    public function testExample7()
    {
        global $_example7;

        $dom = DOMDocument::loadXML($_example7);

        $rss = $dom->documentElement;

        $channels = $dom->getElementsByTagName('channel');

        $channel = $channels->item(0);

        $items = $channel->getElementsByTagName('item');

        $item = $items->item(0);

        $act = new Activity($item, $channel);

        $this->assertEquals(ActivityVerb::POST, $act->verb);
        $this->assertEquals('http://evanpro.posterous.com/checking-out-captain-bones', $act->link);
        $this->assertEquals('http://evanpro.posterous.com/checking-out-captain-bones', $act->id);
        $this->assertEquals('Checking out captain bones', $act->title);
        $this->assertEquals(1269095551, $act->time);

        $actor = $act->actor;

        $this->assertEquals(ActivityObject::PERSON, $actor->type);
        $this->assertEquals('http://posterous.com/people/3sDslhaepotz', $actor->id);
        $this->assertEquals('Evan Prodromou', $actor->title);
        $this->assertNull($actor->summary);
        $this->assertNull($actor->content);
        $this->assertEquals('http://posterous.com/people/3sDslhaepotz', $actor->link);
        $this->assertNull($actor->source);
        $this->assertTrue(is_array($actor->avatarLinks));
        $this->assertEquals(1, count($actor->avatarLinks));
        $this->assertEquals('http://files.posterous.com/user_profile_pics/480326/2009-08-05-142447.jpg',
                            $actor->avatarLinks[0]->url);
        $this->assertNotNull($actor->poco);
        $this->assertEquals('evanpro', $actor->poco->preferredUsername);
        $this->assertEquals('Evan Prodromou', $actor->poco->displayName);
        $this->assertNull($actor->poco->note);
        $this->assertNull($actor->poco->address);
        $this->assertEquals(0, count($actor->poco->urls));
    }

    // Media test - cliqset
    public function testExample8()
    {
        global $_example8;
        $dom = DOMDocument::loadXML($_example8);

        $feed = $dom->documentElement;

        $entries = $feed->getElementsByTagName('entry');

        $entry = $entries->item(0);

        $act = new Activity($entry, $feed);

        $this->assertFalse(empty($act));
        $this->assertEquals($act->time, 1269221753);
        $this->assertEquals($act->verb, ActivityVerb::POST);
        $this->assertEquals($act->summary, 'zcopley posted 5 photos on Flickr');

        $this->assertFalse(empty($act->objects));
        $this->assertEquals(sizeof($act->objects), 5);

        $this->assertEquals($act->objects[0]->type, ActivityObject::PHOTO);
        $this->assertEquals($act->objects[0]->title, 'IMG_1368');
        $this->assertNull($act->objects[0]->description);
        $this->assertEquals(
            $act->objects[0]->thumbnail,
            'http://media.cliqset.com/6f6fbee9d7dfbffc73b6ef626275eb5f_thumb.jpg'
        );
        $this->assertEquals(
            $act->objects[0]->link,
            'http://www.flickr.com/photos/zcopley/4452933806/'
        );

        $this->assertEquals($act->objects[1]->type, ActivityObject::PHOTO);
        $this->assertEquals($act->objects[1]->title, 'IMG_1365');
        $this->assertNull($act->objects[1]->description);
        $this->assertEquals(
            $act->objects[1]->thumbnail,
            'http://media.cliqset.com/b8f3932cd0bba1b27f7c8b3ef986915e_thumb.jpg'
        );
        $this->assertEquals(
            $act->objects[1]->link,
            'http://www.flickr.com/photos/zcopley/4442630390/'
        );

        $this->assertEquals($act->objects[2]->type, ActivityObject::PHOTO);
        $this->assertEquals($act->objects[2]->title, 'Classic');
        $this->assertEquals(
            $act->objects[2]->description,
            '-Powered by pikchur.com/n0u'
        );
        $this->assertEquals(
            $act->objects[2]->thumbnail,
            'http://media.cliqset.com/fc54c15f850b7a9a8efa644087a48c91_thumb.jpg'
        );
        $this->assertEquals(
            $act->objects[2]->link,
            'http://www.flickr.com/photos/zcopley/4430754103/'
        );

        $this->assertEquals($act->objects[3]->type, ActivityObject::PHOTO);
        $this->assertEquals($act->objects[3]->title, 'IMG_1363');
        $this->assertNull($act->objects[3]->description);

        $this->assertEquals(
            $act->objects[3]->thumbnail,
            'http://media.cliqset.com/4b1d307c9217e2114391a8b229d612cb_thumb.jpg'
        );
        $this->assertEquals(
            $act->objects[3]->link,
            'http://www.flickr.com/photos/zcopley/4416969717/'
        );

        $this->assertEquals($act->objects[4]->type, ActivityObject::PHOTO);
        $this->assertEquals($act->objects[4]->title, 'IMG_1361');
        $this->assertNull($act->objects[4]->description);

        $this->assertEquals(
            $act->objects[4]->thumbnail,
            'http://media.cliqset.com/23d9b4b96b286e0347d36052f22f6e60_thumb.jpg'
        );
        $this->assertEquals(
            $act->objects[4]->link,
            'http://www.flickr.com/photos/zcopley/4417734232/'
        );

    }

    public function testAtomContent()
    {
        $tests = array(array("<content>Some regular plain text.</content>",
                             "Some regular plain text."),
                       array("<content>&lt;b&gt;this is not HTML&lt;/b&gt;</content>",
                             "&lt;b&gt;this is not HTML&lt;/b&gt;"),
                       array("<content type='html'>Some regular plain HTML.</content>",
                             "Some regular plain HTML."),
                       array("<content type='html'>&lt;b&gt;this is too HTML&lt;/b&gt;</content>",
                             "<b>this is too HTML</b>"),
                       array("<content type='html'>&amp;lt;b&amp;gt;but this is not HTML!&amp;lt;/b&amp;gt;</content>",
                             "&lt;b&gt;but this is not HTML!&lt;/b&gt;"),
                       array("<content type='xhtml'><div xmlns='http://www.w3.org/1999/xhtml'>Some regular plain XHTML.</div></content>",
                             "Some regular plain XHTML."),
                       array("<content type='xhtml'><div xmlns='http://www.w3.org/1999/xhtml'><b>This is some XHTML!</b></div></content>",
                             "<b>This is some XHTML!</b>"),
                       array("<content type='xhtml'><div xmlns='http://www.w3.org/1999/xhtml'>&lt;b&gt;This is not some XHTML!&lt;/b&gt;</div></content>",
                             "&lt;b&gt;This is not some XHTML!&lt;/b&gt;"),
                       array("<content type='xhtml'><div xmlns='http://www.w3.org/1999/xhtml'>&amp;lt;b&amp;gt;This is not some XHTML either!&amp;lt;/b&amp;gt;</div></content>",
                             "&amp;lt;b&amp;gt;This is not some XHTML either!&amp;lt;/b&amp;gt;"));
        foreach ($tests as $data) {
            list($source, $output) = $data;
            $xml = "<entry xmlns='http://www.w3.org/2005/Atom'>" .
                   "<id>http://example.com/fakeid</id>" .
                   "<author><name>Test</name></author>" .
                   "<title>Atom content tests</title>" .
                   $source .
                   "</entry>";
            $dom = DOMDocument::loadXML($xml);
            $act = new Activity($dom->documentElement);

            $this->assertFalse(empty($act));
            $this->assertEquals($output, trim($act->content));
        }
    }

    public function testRssContent()
    {
        $tests = array(array("<content:encoded>Some regular plain HTML.</content:encoded>",
                             "Some regular plain HTML."),
                       array("<content:encoded>Some &lt;b&gt;exciting bold HTML&lt;/b&gt;</content:encoded>",
                             "Some <b>exciting bold HTML</b>"),
                       array("<content:encoded>Some &amp;lt;b&amp;gt;escaped non-HTML.&amp;lt;/b&amp;gt;</content:encoded>",
                             "Some &lt;b&gt;escaped non-HTML.&lt;/b&gt;"),
                       array("<description>Some plain text.</description>",
                             "Some plain text."),
                       array("<description>Some &lt;b&gt;non-HTML text&lt;/b&gt;</description>",
                             "Some &lt;b&gt;non-HTML text&lt;/b&gt;"),
                       array("<description>Some &amp;lt;b&amp;gt;double-escaped text&amp;lt;/b&amp;gt;</description>",
                             "Some &amp;lt;b&amp;gt;double-escaped text&amp;lt;/b&amp;gt;"));
        foreach ($tests as $data) {
            list($source, $output) = $data;
            $xml = "<item xmlns:content='http://purl.org/rss/1.0/modules/content/'>" .
                   "<guid>http://example.com/fakeid</guid>" .
                   "<title>RSS content tests</title>" .
                   $source .
                   "</item>";
            $dom = DOMDocument::loadXML($xml);
            $act = new Activity($dom->documentElement);

            $this->assertFalse(empty($act));
            $this->assertEquals($output, trim($act->content));
        }
    }

}

$_example1 = <<<EXAMPLE1
<?xml version='1.0' encoding='UTF-8'?>
<entry xmlns='http://www.w3.org/2005/Atom' xmlns:activity='http://activitystrea.ms/spec/1.0/'>
  <id>tag:versioncentral.example.org,2009:/commit/1643245</id>
  <published>2009-06-01T12:54:00Z</published>
  <title>Geraldine committed a change to yate</title>
  <content type="xhtml">Geraldine just committed a change to yate on VersionCentral</content>
  <link rel="alternate" type="text/html"
        href="http://versioncentral.example.org/geraldine/yate/commit/1643245" />
  <activity:verb>http://activitystrea.ms/schema/1.0/post</activity:verb>
  <activity:verb>http://versioncentral.example.org/activity/commit</activity:verb>
  <activity:object>
    <activity:object-type>http://versioncentral.example.org/activity/changeset</activity:object-type>
    <id>tag:versioncentral.example.org,2009:/change/1643245</id>
    <title>Punctuation Changeset</title>
    <summary>Fixing punctuation because it makes it more readable.</summary>
    <link rel="alternate" type="text/html" href="..." />
  </activity:object>
</entry>
EXAMPLE1;

$_example2 = <<<EXAMPLE2
<?xml version='1.0' encoding='UTF-8'?>
<entry xmlns='http://www.w3.org/2005/Atom' xmlns:activity='http://activitystrea.ms/spec/1.0/'>
  <id>tag:photopanic.example.com,2008:activity01</id>
  <title>Geraldine posted a Photo on PhotoPanic</title>
  <published>2008-11-02T15:29:00Z</published>
  <link rel="alternate" type="text/html" href="/geraldine/activities/1" />
  <activity:verb>
  http://activitystrea.ms/schema/1.0/post
  </activity:verb>
  <activity:object>
    <id>tag:photopanic.example.com,2008:photo01</id>
    <title>My Cat</title>
    <published>2008-11-02T15:29:00Z</published>
    <link rel="alternate" type="text/html" href="/geraldine/photos/1" />
    <activity:object-type>
      tag:atomactivity.example.com,2008:photo
    </activity:object-type>
    <source>
      <title>Geraldine's Photos</title>
      <link rel="self" type="application/atom+xml" href="/geraldine/photofeed.xml" />
      <link rel="alternate" type="text/html" href="/geraldine/" />
    </source>
  </activity:object>
  <content type="html">
     &lt;p&gt;Geraldine posted a Photo on PhotoPanic&lt;/p&gt;
     &lt;img src="/geraldine/photo1.jpg"&gt;
  </content>
</entry>
EXAMPLE2;

$_example3 = <<<EXAMPLE3
<?xml version="1.0" encoding="utf-8"?>

<feed xmlns="http://www.w3.org/2005/Atom">

    <title>Example Feed</title>
    <subtitle>A subtitle.</subtitle>
    <link href="http://example.org/feed/" rel="self" />
    <link href="http://example.org/" />
    <id>urn:uuid:60a76c80-d399-11d9-b91C-0003939e0af6</id>
    <updated>2003-12-13T18:30:02Z</updated>
    <author>
        <name>John Doe</name>
        <email>johndoe@example.com</email>
    </author>

    <entry>
        <title>Atom-Powered Robots Run Amok</title>
        <link href="http://example.org/2003/12/13/atom03" />
        <link rel="alternate" type="text/html" href="http://example.org/2003/12/13/atom03.html"/>
        <link rel="edit" href="http://example.org/2003/12/13/atom03/edit"/>
        <id>urn:uuid:1225c695-cfb8-4ebb-aaaa-80da344efa6a</id>
        <updated>2003-12-13T18:30:02Z</updated>
        <summary>Some text.</summary>
    </entry>

</feed>
EXAMPLE3;

$_example4 = <<<EXAMPLE4
<?xml version='1.0' encoding='UTF-8'?>
<entry xmlns="http://www.w3.org/2005/Atom" xmlns:thr="http://purl.org/syndication/thread/1.0" xmlns:georss="http://www.georss.org/georss" xmlns:activity="http://activitystrea.ms/spec/1.0/" xmlns:ostatus="http://ostatus.org/schema/1.0">
 <title>@evan now is the time for all good men to come to the aid of their country. #thetime</title>
 <summary>@evan now is the time for all good men to come to the aid of their country. #thetime</summary>
<author>
 <name>spock</name>
 <uri>http://example.net/user/2</uri>
</author>
<activity:actor>
 <activity:object-type>http://activitystrea.ms/schema/1.0/person</activity:object-type>
 <id>http://example.net/user/2</id>
 <title>spock</title>
 <link type="image/png" rel="avatar" href="http://example.net/theme/identica/default-avatar-profile.png"></link>
</activity:actor>
 <link rel="alternate" type="text/html" href="http://example.net/notice/14"/>
 <id>http://example.net/notice/14</id>
 <published>2010-02-19T02:52:38+00:00</published>
 <updated>2010-02-19T02:52:38+00:00</updated>
 <link rel="related" href="http://example.net/notice/12"/>
 <thr:in-reply-to ref="http://example.net/notice/12" href="http://example.net/notice/12"></thr:in-reply-to>
 <link rel="ostatus:conversation" href="http://example.net/conversation/11"/>
 <link rel="ostatus:attention" href="http://example.net/user/1"/>
 <content type="html">@&lt;span class=&quot;vcard&quot;&gt;&lt;a href=&quot;http://example.net/user/1&quot; class=&quot;url&quot;&gt;&lt;span class=&quot;fn nickname&quot;&gt;evan&lt;/span&gt;&lt;/a&gt;&lt;/span&gt; now is the time for all good men to come to the aid of their country. #&lt;span class=&quot;tag&quot;&gt;&lt;a href=&quot;http://example.net/tag/thetime&quot; rel=&quot;tag&quot;&gt;thetime&lt;/a&gt;&lt;/span&gt;</content>
 <category term="thetime"></category>
</entry>
EXAMPLE4;

$_example5 = <<<EXAMPLE5
<?xml version="1.0" encoding="UTF-8"?>
<feed xml:lang="en-US" xmlns="http://www.w3.org/2005/Atom" xmlns:thr="http://purl.org/syndication/thread/1.0" xmlns:georss="http://www.georss.org/georss" xmlns:activity="http://activitystrea.ms/spec/1.0/" xmlns:poco="http://portablecontacts.net/spec/1.0" xmlns:ostatus="http://ostatus.org/schema/1.0">
 <id>3</id>
 <title>testuser timeline</title>
 <subtitle>Updates from testuser on Zach Dev!</subtitle>
 <logo>http://example.net/mysite/avatar/3-96-20100224004207.jpeg</logo>
 <updated>2010-02-24T06:38:49+00:00</updated>
<author>
 <name>testuser</name>
 <uri>http://example.net/mysite/user/3</uri>

</author>
 <link href="http://example.net/mysite/testuser" rel="alternate" type="text/html"/>
 <link href="http://example.net/mysite/api/statuses/user_timeline/3.atom" rel="self" type="application/atom+xml"/>
 <link href="http://example.net/mysite/main/sup#3" rel="http://api.friendfeed.com/2008/03#sup" type="application/json"/>
 <link href="http://example.net/mysite/main/push/hub" rel="hub"/>
 <link href="http://example.net/mysite/main/salmon/user/3" rel="salmon"/>
<activity:subject>
 <activity:object-type>http://activitystrea.ms/schema/1.0/person</activity:object-type>
 <id>http://example.net/mysite/user/3</id>
 <title>Test User</title>
 <link rel="alternate" type="text/html" href="http://example.net/mysite/testuser"/>
 <link type="image/jpeg" rel="avatar" href="http://example.net/mysite/avatar/3-96-20100224004207.jpeg"/>
 <georss:point>37.7749295 -122.4194155</georss:point>

<poco:preferredUsername>testuser</poco:preferredUsername>
<poco:displayName>Test User</poco:displayName>
<poco:note>Just another test user.</poco:note>
<poco:address>
 <poco:formatted>San Francisco, CA</poco:formatted>
</poco:address>
<poco:urls>
 <poco:type>homepage</poco:type>
 <poco:value>http://example.com/blog.html</poco:value>
 <poco:primary>true</poco:primary>

</poco:urls>
</activity:subject>
<entry>
 <title>Hey man, is that Freedom Code?! #freedom #hippy</title>
 <summary>Hey man, is that Freedom Code?! #freedom #hippy</summary>
<author>
 <name>testuser</name>
 <uri>http://example.net/mysite/user/3</uri>
</author>
<activity:actor>
 <activity:object-type>http://activitystrea.ms/schema/1.0/person</activity:object-type>
 <id>http://example.net/mysite/user/3</id>
 <title>Test User</title>
 <link rel="alternate" type="text/html" href="http://example.net/mysite/testuser"/>
 <link type="image/jpeg" rel="avatar" href="http://example.net/mysite/avatar/3-96-20100224004207.jpeg"/>
 <georss:point>37.7749295 -122.4194155</georss:point>

<poco:preferredUsername>testuser</poco:preferredUsername>
<poco:displayName>Test User</poco:displayName>
<poco:note>Just another test user.</poco:note>
<poco:address>
 <poco:formatted>San Francisco, CA</poco:formatted>
</poco:address>
<poco:urls>
 <poco:type>homepage</poco:type>
 <poco:value>http://example.com/blog.html</poco:value>
 <poco:primary>true</poco:primary>

</poco:urls>
</activity:actor>
 <link rel="alternate" type="text/html" href="http://example.net/mysite/notice/7"/>
 <id>http://example.net/mysite/notice/7</id>
 <published>2010-02-24T00:53:06+00:00</published>
 <updated>2010-02-24T00:53:06+00:00</updated>
 <link rel="ostatus:conversation" href="http://example.net/mysite/conversation/7"/>
 <content type="html">Hey man, is that Freedom Code?! #&lt;span class=&quot;tag&quot;&gt;&lt;a href=&quot;http://example.net/mysite/tag/freedom&quot; rel=&quot;tag&quot;&gt;freedom&lt;/a&gt;&lt;/span&gt; #&lt;span class=&quot;tag&quot;&gt;&lt;a href=&quot;http://example.net/mysite/tag/hippy&quot; rel=&quot;tag&quot;&gt;hippy&lt;/a&gt;&lt;/span&gt;</content>
 <georss:point>37.8313160 -122.2852473</georss:point>

</entry>
</feed>
EXAMPLE5;

$_example6 = <<<EXAMPLE6
<?xml version="1.0" encoding="UTF-8"?>
<rss version="2.0"
	xmlns:content="http://purl.org/rss/1.0/modules/content/"
	xmlns:wfw="http://wellformedweb.org/CommentAPI/"
	xmlns:dc="http://purl.org/dc/elements/1.1/"
	xmlns:atom="http://www.w3.org/2005/Atom"
	xmlns:sy="http://purl.org/rss/1.0/modules/syndication/"
	xmlns:slash="http://purl.org/rss/1.0/modules/slash/"
	xmlns:georss="http://www.georss.org/georss" xmlns:geo="http://www.w3.org/2003/01/geo/wgs84_pos#" xmlns:media="http://search.yahoo.com/mrss/"
	>

	<channel>
		<title>WordPress.com News</title>
		<atom:link href="http://en.blog.wordpress.com/feed/" rel="self" type="application/rss+xml" />
		<link>http://en.blog.wordpress.com</link>
		<description>The latest news on WordPress.com and the WordPress community.</description>
		<lastBuildDate>Thu, 18 Mar 2010 23:25:35 +0000</lastBuildDate>

		<generator>http://wordpress.com/</generator>
		<language>en</language>
		<sy:updatePeriod>hourly</sy:updatePeriod>
		<sy:updateFrequency>1</sy:updateFrequency>
		<cloud domain='en.blog.wordpress.com' port='80' path='/?rsscloud=notify' registerProcedure='' protocol='http-post' />
		<image>
			<url>http://www.gravatar.com/blavatar/e6392390e3bcfadff3671c5a5653d95b?s=96&#038;d=http://s2.wp.com/i/buttonw-com.png</url>
			<title>WordPress.com News</title>
			<link>http://en.blog.wordpress.com</link>
		</image>
		<atom:link rel="search" type="application/opensearchdescription+xml" href="http://en.blog.wordpress.com/osd.xml" title="WordPress.com News" />
		<atom:link rel='hub' href='http://en.blog.wordpress.com/?pushpress=hub'/>

		<item>
			<title>Rub-a-Dub-Dub in the PubSubHubbub</title>
			<link>http://en.blog.wordpress.com/2010/03/03/rub-a-dub-dub-in-the-pubsubhubbub/</link>
			<comments>http://en.blog.wordpress.com/2010/03/03/rub-a-dub-dub-in-the-pubsubhubbub/#comments</comments>
			<pubDate>Wed, 03 Mar 2010 16:48:12 +0000</pubDate>
			<dc:creator>Joseph Scott</dc:creator>

			<category><![CDATA[Feeds]]></category>
			<category><![CDATA[atom]]></category>
			<category><![CDATA[pubsubhubbub]]></category>
			<category><![CDATA[rss]]></category>

			<guid isPermaLink="false">http://en.blog.wordpress.com/?p=3857</guid>
			<description><![CDATA[From the tongue twisting name department we welcome PubSubHubbub, or as some people have shortened it to: PuSH.  Like rssCloud, PuSH is a way for services that subscribe to updates from your blog (think Google Reader, Bloglines or Netvibes) to get updates even faster.  In a nutshell, instead of having to periodically ask [...]<img alt="" border="0" src="http://stats.wordpress.com/b.gif?host=en.blog.wordpress.com&blog=3584907&post=3857&subd=en.blog&ref=&feed=1" />]]></description>
				<content:encoded><![CDATA[<p>From the tongue twisting name department we welcome <a href="http://code.google.com/p/pubsubhubbub/">PubSubHubbub</a>, or as some people have shortened it to: PuSH.  Like <a href="http://en.blog.wordpress.com/2009/09/07/rss-in-the-clouds/">rssCloud</a>, PuSH is a way for services that subscribe to updates from your blog (think Google Reader, Bloglines or Netvibes) to get updates even faster.  In a nutshell, instead of having to periodically ask your blog if there are any updates they can now register to automatically receive updates each time you publish new content.  In most cases these updates are sent out within a second or two of when you hit the publish button.</p>
	<p>Today we&#8217;ve turned on PuSH support for the more than 10.5 million blogs on WordPress.com.  There&#8217;s nothing to configure, it&#8217;s working right now behind the scenes to help others keep up to date with your posts.</p>
	<p>For those using the WordPress.org software we are releasing a new PuSH plugin: <a href="http://wordpress.org/extend/plugins/pushpress/">PuSHPress</a>.  This plugin differs from the current PuSH related plugins by including a built-in hub.</p>
	<p>For more PuSH related reading check out the <a href="http://code.google.com/p/pubsubhubbub/">PubSubHubbub project site</a> and <a href="http://groups.google.com/group/pubsubhubbub?pli=1">Google Group</a>.  And if you really want to geek out there&#8217;s always the <a href="http://pubsubhubbub.googlecode.com/svn/trunk/pubsubhubbub-core-0.3.html">PubSubHubbub Spec</a> <img src='http://s.wordpress.com/wp-includes/images/smilies/icon_smile.gif' alt=':-)' class='wp-smiley' /> </p>
	<br />  <a rel="nofollow" href="http://feeds.wordpress.com/1.0/gocomments/en.blog.wordpress.com/3857/"><img alt="" border="0" src="http://feeds.wordpress.com/1.0/comments/en.blog.wordpress.com/3857/" /></a> <a rel="nofollow" href="http://feeds.wordpress.com/1.0/godelicious/en.blog.wordpress.com/3857/"><img alt="" border="0" src="http://feeds.wordpress.com/1.0/delicious/en.blog.wordpress.com/3857/" /></a> <a rel="nofollow" href="http://feeds.wordpress.com/1.0/gostumble/en.blog.wordpress.com/3857/"><img alt="" border="0" src="http://feeds.wordpress.com/1.0/stumble/en.blog.wordpress.com/3857/" /></a> <a rel="nofollow" href="http://feeds.wordpress.com/1.0/godigg/en.blog.wordpress.com/3857/"><img alt="" border="0" src="http://feeds.wordpress.com/1.0/digg/en.blog.wordpress.com/3857/" /></a> <a rel="nofollow" href="http://feeds.wordpress.com/1.0/goreddit/en.blog.wordpress.com/3857/"><img alt="" border="0" src="http://feeds.wordpress.com/1.0/reddit/en.blog.wordpress.com/3857/" /></a> <img alt="" border="0" src="http://stats.wordpress.com/b.gif?host=en.blog.wordpress.com&blog=3584907&post=3857&subd=en.blog&ref=&feed=1" />]]></content:encoded>
				<wfw:commentRss>http://en.blog.wordpress.com/2010/03/03/rub-a-dub-dub-in-the-pubsubhubbub/feed/</wfw:commentRss>

			<slash:comments>96</slash:comments>

			<media:content url="http://1.gravatar.com/avatar/582b66ad5ae1b69c7601a990cb9a661a?s=96&#38;d=identicon" medium="image">
				<media:title type="html">josephscott</media:title>
			</media:content>
		</item>
	</channel>
</rss>
EXAMPLE6;

$_example7 = <<<EXAMPLE7
<?xml version="1.0" encoding="UTF-8"?>
	<rss xmlns:atom="http://www.w3.org/2005/Atom" version="2.0" xmlns:posterous="http://posterous.com/help/rss/1.0" xmlns:dc="http://purl.org/dc/elements/1.1/" xmlns:media="http://search.yahoo.com/mrss/">
	  <channel>
	    <title>evanpro's posterous</title>
	    <link>http://evanpro.posterous.com</link>
	    <description>Most recent posts at evanpro's posterous</description>
	    <generator>posterous.com</generator>
	    <link type="application/json" xmlns="http://www.w3.org/2005/Atom" rel="http://api.friendfeed.com/2008/03#sup" href="http://posterous.com/api/sup_update#56bcc5eb7"/>
	    <atom:link rel="self" href="http://evanpro.posterous.com/rss.xml"/>
	    <atom:link rel="hub" href="http://posterous.superfeedr.com"/>
	    <item>
	      <pubDate>Sat, 20 Mar 2010 07:32:31 -0700</pubDate>
	      <title>Checking out captain bones</title>
	      <link>http://evanpro.posterous.com/checking-out-captain-bones</link>
	      <guid>http://evanpro.posterous.com/checking-out-captain-bones</guid>
	      <description>
	        <![CDATA[<p>
		<p>Bones!</p>

	</p>

	<p><a href="http://evanpro.posterous.com/checking-out-captain-bones">Permalink</a>

		| <a href="http://evanpro.posterous.com/checking-out-captain-bones#comment">Leave a comment&nbsp;&nbsp;&raquo;</a>

	</p>]]>
	      </description>
	      <posterous:author>
	        <posterous:userImage>http://files.posterous.com/user_profile_pics/480326/2009-08-05-142447.jpg</posterous:userImage>
	        <posterous:profileUrl>http://posterous.com/people/3sDslhaepotz</posterous:profileUrl>
	        <posterous:firstName>Evan</posterous:firstName>
	        <posterous:lastnNme>Prodromou</posterous:lastnNme>
	        <posterous:nickName>evanpro</posterous:nickName>
	        <posterous:displayName>Evan Prodromou</posterous:displayName>
	      </posterous:author>
	    </item>
	</channel>
</rss>
EXAMPLE7;

$_example8 = <<<EXAMPLE8
<?xml version="1.0"?>
<feed xmlns="http://www.w3.org/2005/Atom">
    <link href="http://pubsubhubbub.appspot.com/" rel="hub"/>
    <title type="text">Activity Stream for: zcopley</title>
    <id>http://cliqset.com/feed/atom?uid=zcopley</id>
    <entry xmlns:service="http://activitystrea.ms/service-provider" xmlns:activity="http://activitystrea.ms/spec/1.0/">
        <thr:total xmlns:thr="http://purl.org/syndication/thread/1.0">0</thr:total>
        <activity:verb>http://activitystrea.ms/schema/1.0/post</activity:verb>
        <published>2010-03-22T01:35:53.000Z</published>
        <service:provider>
            <name>flickr</name>
            <uri>http://flickr.com</uri>
            <icon>http://cliqset-services.s3.amazonaws.com/flickr.png</icon>
        </service:provider>
        <activity:object>
            <activity:object-type>http://activitystrea.ms/schema/1.0/photo</activity:object-type>
            <title type="text">IMG_1368</title>
            <link type="image/jpeg" rel="preview" href="http://media.cliqset.com/6f6fbee9d7dfbffc73b6ef626275eb5f_thumb.jpg"/>
            <link rel="alternate" type="text/html" href="http://www.flickr.com/photos/zcopley/4452933806/"/>
        </activity:object>
        <activity:object>
            <activity:object-type>http://activitystrea.ms/schema/1.0/photo</activity:object-type>
            <title type="text">IMG_1365</title>
            <link type="image/jpeg" rel="preview" href="http://media.cliqset.com/b8f3932cd0bba1b27f7c8b3ef986915e_thumb.jpg"/>
            <link rel="alternate" type="text/html" href="http://www.flickr.com/photos/zcopley/4442630390/"/>
        </activity:object>
        <activity:object xmlns:media="http://purl.org/syndication/atommedia">
            <activity:object-type>http://activitystrea.ms/schema/1.0/photo</activity:object-type>
            <title type="text">Classic</title>
            <link type="image/jpeg" rel="preview" href="http://media.cliqset.com/fc54c15f850b7a9a8efa644087a48c91_thumb.jpg"/>
            <link rel="alternate" type="text/html" href="http://www.flickr.com/photos/zcopley/4430754103/"/>
            <media:description type="text">-Powered by pikchur.com/n0u</media:description>
        </activity:object>
        <activity:object>
            <activity:object-type>http://activitystrea.ms/schema/1.0/photo</activity:object-type>
            <title type="text">IMG_1363</title>
            <link type="image/jpeg" rel="preview" href="http://media.cliqset.com/4b1d307c9217e2114391a8b229d612cb_thumb.jpg"/>
            <link rel="alternate" type="text/html" href="http://www.flickr.com/photos/zcopley/4416969717/"/>
        </activity:object>
        <activity:object>
            <activity:object-type>http://activitystrea.ms/schema/1.0/photo</activity:object-type>
            <title type="text">IMG_1361</title>
            <link type="image/jpeg" rel="preview" href="http://media.cliqset.com/23d9b4b96b286e0347d36052f22f6e60_thumb.jpg"/>
            <link rel="alternate" type="text/html" href="http://www.flickr.com/photos/zcopley/4417734232/"/>
        </activity:object>
        <title type="text">zcopley posted some photos on Flickr</title>
        <summary type="text">zcopley posted 5 photos on Flickr</summary>
        <category scheme="http://schemas.cliqset.com/activity/categories/1.0" term="PhotoPosted" label="Photo Posted"/>
        <updated>2010-03-22T20:46:42.778Z</updated>
        <id>tag:cliqset.com,2010-03-22:/user/zcopley/SVgAZubGhtAnSAee</id>
        <link href="http://cliqset.com/user/zcopley/SVgAZubGhtAnSAee" type="text/xhtml" rel="alternate" title="zcopley posted some photos on Flickr"/>
        <author>
            <name>zcopley</name>
            <uri>http://cliqset.com/user/zcopley</uri>
        </author>
        <activity:actor xmlns:poco="http://portablecontacts.net/spec/1.0">
            <activity:object-type>http://activitystrea.ms/schema/1.0/person</activity:object-type>
            <id>zcopley</id>
            <poco:name>
                <poco:givenName>Zach</poco:givenName>
                <poco:familyName>Copley</poco:familyName>
            </poco:name>
            <link xmlns:media="http://purl.org/syndication/atommedia" type="image/png" rel="avatar" href="http://dynamic.cliqset.com/avatar/zcopley?s=80" media:height="80" media:width="80"/>
            <link xmlns:media="http://purl.org/syndication/atommedia" type="image/png" rel="avatar" href="http://dynamic.cliqset.com/avatar/zcopley?s=120" media:height="120" media:width="120"/>
            <link xmlns:media="http://purl.org/syndication/atommedia" type="image/png" rel="avatar" href="http://dynamic.cliqset.com/avatar/zcopley?s=200" media:height="200" media:width="200"/>
        </activity:actor>
    </entry>
</feed>
EXAMPLE8;

$_example9 = <<<EXAMPLE9
<?xml version="1.0" encoding="utf-8"?>
<feed xmlns="http://www.w3.org/2005/Atom" xmlns:thr="http://purl.org/syndication/thread/1.0" xmlns:media="http://search.yahoo.com/mrss" xmlns:activity="http://activitystrea.ms/spec/1.0/">
    <link rel="self" type="application/atom+xml" href="http://buzz.googleapis.com/feeds/117848251937215158042/public/posted"/>
    <link rel="hub" href="http://pubsubhubbub.appspot.com/"/>
    <title type="text">Google Buzz</title>
    <updated>2010-03-22T01:55:53.596Z</updated>
    <id>tag:google.com,2009:buzz-feed/public/posted/117848251937215158042</id>
    <generator>Google - Google Buzz</generator>
    <entry>
        <title type="html">Buzz by Zach Copley from Flickr</title>
        <summary type="text">IMG_1366</summary>
        <published>2010-03-18T04:29:23.000Z</published>
        <updated>2010-03-18T05:14:03.325Z</updated>
        <id>tag:google.com,2009:buzz/z12zwdhxowq2d13q204cjr04kzu0cns5gh0</id>
        <link rel="alternate" type="text/html" href="http://www.google.com/buzz/117848251937215158042/ZU7b6mHJEmC/IMG-1366"/>
        <author>
            <name>Zach Copley</name>
            <uri>http://www.google.com/profiles/zcopley</uri>
        </author>
        <content type="html">&lt;div&gt;IMG_1366&lt;/div&gt;</content>
        <link rel="enclosure" href="http://www.flickr.com/photos/22823034@N00/4442630700" type="image/jpeg" title="IMG_1366"/>
        <media:content url="http://www.flickr.com/photos/22823034@N00/4442630700" type="image/jpeg" medium="image">
            <media:title>IMG_1366</media:title>
            <media:player url="http://farm5.static.flickr.com/4053/4442630700_980b19a1a6_o.jpg" height="1600" width="1200"/>
        </media:content>
        <link rel="enclosure" href="http://www.flickr.com/photos/22823034@N00/4442630390" type="image/jpeg" title="IMG_1365"/>
        <media:content url="http://www.flickr.com/photos/22823034@N00/4442630390" type="image/jpeg" medium="image">
            <media:title>IMG_1365</media:title>
            <media:player url="http://farm5.static.flickr.com/4043/4442630390_62da5560ae_o.jpg" height="1200" width="1600"/>
        </media:content>
        <activity:verb>http://activitystrea.ms/schema/1.0/post</activity:verb>
        <activity:object>
            <activity:object-type>http://activitystrea.ms/schema/1.0/photo</activity:object-type>
            <id>tag:google.com,2009:buzz/z12zwdhxowq2d13q204cjr04kzu0cns5gh0</id>
            <title>Buzz by Zach Copley from Flickr</title>
            <content type="html">&lt;div&gt;IMG_1366&lt;/div&gt;</content>
            <link rel="enclosure" href="http://www.flickr.com/photos/22823034@N00/4442630700" type="image/jpeg" title="IMG_1366"/>
            <link rel="enclosure" href="http://www.flickr.com/photos/22823034@N00/4442630390" type="image/jpeg" title="IMG_1365"/>
        </activity:object>
        <link rel="replies" type="application/atom+xml" href="http://buzz.googleapis.com/feeds/117848251937215158042/comments/z12zwdhxowq2d13q204cjr04kzu0cns5gh0" thr:count="0"/>
        <thr:total>0</thr:total>
    </entry>
</feed>
EXAMPLE9;
