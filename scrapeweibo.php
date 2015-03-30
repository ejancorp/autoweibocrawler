<?php /*
 *
 * Auto Weibo Plus Simple Dom Parser Combo
 * @author     Ej Corpuz @ mobext.ph
 * @version    1.0
 *
 *
 */

include 'autoweibo.php';
include 'domparser/simple_html_dom.php';

class scrapeWeibo extends Auto_Weibo {

    private $filename = 'results/pagesite.html';

    public function __construct($username = NULL, $password = NULL, $client = NULL, $hashserver = NULL, $targetPage = NULL) {

        parent::__construct($username, $password, $client, $hashserver, $targetPage);

    }

    public function getScrape() {

        header('Content-Type: text/html; charset=utf-8');

        $website = parent::initWeibo();
        /* save to file */
        $this -> saveFile($website);

        /*logics*/
        $doms = $this -> setDoms($website);

        /*all data*/
        $datas = $this -> getDatas($doms);

        return $datas;

    }

    private function setDoms($website) {

        preg_match_all('/"html":".[\w\W].+/', $website, $htmltags);
        $countAll = count($htmltags[0]);
        $postarea = stripslashes($htmltags[0][$countAll - 1]);

        //call simple html dom class selector
        $html = new simple_html_dom();
        $html -> load($postarea);
        $list = $html -> find('div[class=WB_feed_type]');

        if ($list) :
            return $list;
        else :
            return FALSE;
        endif;

    }

    private function getDatas($doms) {

        $postArray = array();

        foreach ($doms as $key => $value) {

            $parseFeeds = new simple_html_dom();
            $parseFeeds -> load($value);

            //get weibo post hashtag
            $feedTopic = ($parseFeeds -> find('a[class=a_topic]', 0) ? $parseFeeds -> find('a[class=a_topic]', 0) -> plaintext : '');
            //get weibo post body
            $feedBody = ($parseFeeds -> find('div[node-type=feed_list_content]', 0) ? $parseFeeds -> find('div[node-type=feed_list_content]', 0) -> plaintext : '');
            //get weibo post image source
            $feedImg = ($parseFeeds -> find('img[node-type=feed_list_media_bgimg]', 0) ? $parseFeeds -> find('img[node-type=feed_list_media_bgimg]', 0) -> src : '');
            //get weibo post direct link
            $feedLink = ($parseFeeds -> find('a[node-type=feed_list_item_date]', 0) ? $parseFeeds -> find('a[node-type=feed_list_item_date]', 0) -> attr['href'] : '');
            //get weibo post time
            $feedTime = ($parseFeeds -> find('a[node-type=feed_list_item_date]', 0) ? $parseFeeds -> find('a[node-type=feed_list_item_date]', 0) -> attr['title'] : '');
            //get weibo mid / post id
            $postID = ($parseFeeds -> find('div[action-type=feed_list_item]', 0) ? $parseFeeds -> find('div[action-type=feed_list_item]', 0) -> attr['mid'] : '');

            //get weibo post owner
            $parseOwner = $parseFeeds -> find('div[node-type=feed_list_content]', 0) -> attr;
            $feedOwner = (array_key_exists('nick-name', $parseOwner) ? $parseOwner['nick-name'] : '');

            $postArray[trim($postID)] = array(
                'topic' => trim($feedTopic),
                'body' => trim(ltrim($feedBody, 'n')),
                'pre_image' => $feedImg,
                'med_image' => str_replace('thumbnail', 'bmiddle', $feedImg),
                'owner' => trim($feedOwner),
                'link' => trim($feedLink),
                'time' => trim($feedTime),
                'postid' => trim($postID)
            );
        }

        return $postArray;

    }

    private function saveFile($website) {

        /*save to file*/
        file_put_contents($this -> filename, $website);

    }

}
?>