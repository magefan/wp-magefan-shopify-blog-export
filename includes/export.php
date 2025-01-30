<?php

/**
 * Copyright © Magefan (support@magefan.com). All rights reserved.
 * Please visit Magefan.com for license details (https://magefan.com/end-user-license-agreement).
 */

class Export
{
    const ENTITIES_PER_PAGE = 100;

    protected $entitiesLimit;

    public function setEntitiesLimit($entitiesLimit)
    {
        $this->entitiesLimit = $entitiesLimit;
    }

    public function getEntitiesLimit()
    {
        if (!isset($this->entitiesLimit)) {
            $this->entitiesLimit = self::ENTITIES_PER_PAGE;
        }

        return $this->entitiesLimit;
    }

    public function getCategoryIds()
    {
        global $wpdb;
        $_pref = $wpdb->prefix;

        $sql = 'SELECT
                    t.term_id as old_id
                FROM '.$_pref.'terms t
                LEFT JOIN '.$_pref.'term_taxonomy tt on t.term_id = tt.term_id
                WHERE tt.taxonomy = "category" AND t.slug <> "uncategorized"';

        return $this->getEntityIds($sql);
    }

    public function getTagIds()
    {
        global $wpdb;
        $_pref = $wpdb->prefix;

        $sql = 'SELECT
                    t.term_id as old_id
                FROM '.$_pref.'terms t
                LEFT JOIN '.$_pref.'term_taxonomy tt on t.term_id = tt.term_id
                WHERE tt.taxonomy = "post_tag" AND t.slug <> "uncategorized"';

        return $this->getEntityIds($sql);
    }

    public function getPostIds()
    {
        global $wpdb;
        $_pref = $wpdb->prefix;

        $sql = 'SELECT ID as old_id FROM '.$_pref.'posts WHERE `post_type` = "post"';

        return $this->getEntityIds($sql);
    }

    public function getCommentIds()
    {
        global $wpdb;
        $_pref = $wpdb->prefix;

        $sql = 'SELECT
                        comment_ID as old_id
                        FROM
                            '.$_pref.'comments
                        WHERE
                            `comment_approved`=1';

        return $this->getEntityIds($sql);
    }

    public function getAuthorIds(): array
    {
        global $wpdb;
        $_pref = $wpdb->prefix;

        $sql = 'SELECT
                    DISTINCT u.ID as old_id
                FROM '.$_pref.'users as u';


        return $this->getEntityIds($sql);
    }

    public function getPostMediaPathsNumber()
    {
        global $wpdb;

        $_pref = $wpdb->prefix;

        $sql = 'SELECT wm2.meta_value as old_id
                FROM
                    '.$_pref.'posts p1
                LEFT JOIN
                    '.$_pref.'postmeta wm1
                    ON (
                        wm1.post_id = p1.id
                        AND wm1.meta_value IS NOT NULL
                        AND wm1.meta_key = "_thumbnail_id"
                    )
                LEFT JOIN
                    '.$_pref.'postmeta wm2
                    ON (
                        wm1.meta_value = wm2.post_id
                        AND wm2.meta_key = "_wp_attached_file"
                        AND wm2.meta_value IS NOT NULL
                    )
                WHERE
                    p1.post_type="post"
                ORDER BY
                    p1.post_date DESC';

        return $this->getEntityIds($sql);
    }

    //getAuthorMediaPathsNumber
    public function getAuthorMediaPathsNumber()
    {
        global $wpdb;

        $_pref = $wpdb->prefix;

        $sql = 'SELECT m.user_id as old_id
                FROM
                    '.$_pref.'usermeta m1
                WHERE
                    m.meta_key="avatar" AND m.meta_value > 0
               ';

        return $this->getEntityIds($sql);
    }

    public function getPostMediaPaths(int $offset): array
    {
        global $wpdb;

        $_pref = $wpdb->prefix;
        $offset--;

        $sql = 'SELECT * FROM ' . $_pref . 'posts WHERE `post_type` = "post" LIMIT ' . $this->getEntitiesLimit();

        if ($offset) {
            $offset *= $this->getEntitiesLimit();
            $sql .= ' OFFSET ' . $offset;
        }

        $resultFeaturedImgData = [];

        $result = $wpdb->get_results($sql);
        $arrayPosts = json_decode(json_encode($result), true);
        foreach ($arrayPosts as $post) {
            $featured_img_id = get_post_thumbnail_id($post['ID']);

            $featured_img_url = wp_get_attachment_image_src($featured_img_id, 'full')[0] ?? '';  // Replace 'full' with desired size

            $upload_dir = wp_upload_dir();  // Get the base upload directory
            $base_path = trailingslashit($upload_dir['basedir']);

            $featuredImg = str_replace('//', '/', str_replace($upload_dir['baseurl'], $base_path, $featured_img_url));

            $resultFeaturedImgData[] = ['old_id' => $post['ID'], 'featured_img' => $featuredImg];
        }

        return $resultFeaturedImgData;
    }

    public function getAuthorMediaPaths(int $offset): array
    {
        global $wpdb;

        $_pref = $wpdb->prefix;
        $offset--;

        $sql = 'SELECT m.user_id as old_id, m.meta_value as image_id
                FROM
                    '.$_pref.'usermeta m
                WHERE
                    m.meta_key="avatar" AND m.meta_value > 0
               LIMIT ' . self::ENTITIES_PER_PAGE;

        if ($offset) {
            $offset *= self::ENTITIES_PER_PAGE;
            $sql .= ' OFFSET ' . $offset;
        }

        $resultFeaturedImgData = [];

        $result = $wpdb->get_results($sql);
        $arrayImages = json_decode(json_encode($result), true);
        foreach ($arrayImages as $image) {
            $featured_img_url = wp_get_attachment_image_url($image['image_id'], 'full');
            $upload_dir = wp_upload_dir();  // Get the base upload directory
            $base_path = trailingslashit($upload_dir['basedir']);

            $featuredImg = str_replace('//', '/', str_replace($upload_dir['baseurl'], $base_path, $featured_img_url));

            $resultFeaturedImgData[] = ['old_id' => $image['old_id'], 'featured_img' => $featuredImg];
        }

        return $resultFeaturedImgData;
    }

    public function getEntityIds($sql)
    {
        global $wpdb;

        $entityIds = [];

        $result = $wpdb->get_results($sql);
        $entityIdsArray = json_decode(json_encode($result), true);

        foreach ($entityIdsArray as $entityIdArray) {
            if (isset($entityIdArray['old_id'])) {
                $entityIds[] = $entityIdArray['old_id'];
            }
        }

        return $entityIds;
    }

    public function getCategories(int $offset): array
    {
        global $wpdb;

        $_pref = $wpdb->prefix;
        $offset--;

        $sql = 'SELECT
                    t.term_id as old_id,
                    t.name as title,
                    t.slug as identifier,
                    tt.parent as path
                FROM '.$_pref.'terms t
                LEFT JOIN '.$_pref.'term_taxonomy tt on t.term_id = tt.term_id
                WHERE tt.taxonomy = "category" AND t.slug <> "uncategorized"
                LIMIT 
                ' . $this->getEntitiesLimit();

        if ($offset) {
            $offset *= $this->getEntitiesLimit();
            $sql .= ' OFFSET ' . $offset;
        }

        $result = $wpdb->get_results($sql);
        $array = json_decode(json_encode($result), true);

        //Prepare Categories
        foreach ($array as &$category) {
            $category['is_active'] = 1;
            $category['position'] = 0;
        }

        return $array;
    }

    public function getAuthors(int $offset): array
    {
        global $wpdb;

        $_pref = $wpdb->prefix;
        $offset--;

        $sql = 'SELECT
                    u.ID as old_id,
                    MAX(CASE WHEN m.meta_key = "first_name" THEN m.meta_value END) as firstname,
                    MAX(CASE WHEN m.meta_key = "last_name" THEN m.meta_value END) as lastname,
                    MAX(CASE WHEN m.meta_key = "description" THEN m.meta_value END) as content,
                    u.user_email as email
                FROM '.$_pref.'users u
                LEFT JOIN '.$_pref.'usermeta m on u.ID = m.user_id
                GROUP BY u.ID
                LIMIT
                '  . self::ENTITIES_PER_PAGE;

        if ($offset) {
            $offset *= self::ENTITIES_PER_PAGE;
            $sql .= ' OFFSET ' . $offset;
        }

        $result = $wpdb->get_results($sql);
        $array = json_decode(json_encode($result), true);

        //Prepare Authors
        foreach ($array as &$author) {
            $author['is_active'] = 1;
        }

        return $array;
    }


    public function getTags(int $offset): array
    {
        global $wpdb;

        $_pref = $wpdb->prefix;
        $offset--;

        $sql = 'SELECT
                    t.term_id as old_id,
                    t.name as title,
                    t.slug as identifier,
                    tt.parent as parent_id
                FROM '.$_pref.'terms t
                LEFT JOIN '.$_pref.'term_taxonomy tt on t.term_id = tt.term_id
                WHERE tt.taxonomy = "post_tag" AND t.slug <> "uncategorized" LIMIT '. $this->getEntitiesLimit();

        if ($offset) {
            $offset *= $this->getEntitiesLimit();
            $sql .= ' OFFSET ' . $offset;
        }

        $result = $wpdb->get_results($sql);
        return json_decode(json_encode($result), true);
    }

    public function getPosts(int $offset): array
    {
        global $wpdb;

        $_pref = $wpdb->prefix;
        $offset--;

        $sql = 'SELECT * FROM ' . $_pref . 'posts WHERE `post_type` = "post" LIMIT ' . $this->getEntitiesLimit();

        if ($offset) {
            $offset *= $this->getEntitiesLimit();
            $sql .= ' OFFSET ' . $offset;
        }

        $resultPostData = [];

        $result = $wpdb->get_results($sql);
        $arrayPosts = json_decode(json_encode($result), true);

        $blogId = get_current_blog_id();

        foreach ($arrayPosts as &$post) {
            /* find post categories*/
            $postCategories = [];

            $sql = 'SELECT tt.term_id as term_id FROM ' . $_pref . 'term_relationships tr
                    LEFT JOIN ' . $_pref . 'term_taxonomy tt ON tr.term_taxonomy_id = tt.term_taxonomy_id
                    WHERE tr.`object_id` = "' . $post['ID'] . '" AND tt.taxonomy = "category"';

            $result2 = $wpdb->get_results($sql);
            $arrayCategories = json_decode(json_encode($result2), true);
            foreach ($arrayCategories as $category) {
                $postCategories[] = $category['term_id'];
            }

            /* find post tags*/
            $postTags = [];

            $sql = 'SELECT tt.term_id as term_id FROM ' . $_pref . 'term_relationships tr
                    LEFT JOIN ' . $_pref . 'term_taxonomy tt ON tr.term_taxonomy_id = tt.term_taxonomy_id
                    WHERE tr.`object_id` = "' . $post['ID'] . '" AND tt.taxonomy = "post_tag"';

            $result2 = $wpdb->get_results($sql);
            $arrayTags = json_decode(json_encode($result2), true);
            foreach ($arrayTags as $tag) {
                $postTags[] = $tag['term_id'];
            }

            /* Featured Img */
            $post['featured_img'] = '';

            $sql = 'SELECT wm2.meta_value as featured_img
                FROM
                    '.$_pref.'posts p1
                LEFT JOIN
                    '.$_pref.'postmeta wm1
                    ON (
                        wm1.post_id = p1.id
                        AND wm1.meta_value IS NOT NULL
                        AND wm1.meta_key = "_thumbnail_id"
                    )
                LEFT JOIN
                    '.$_pref.'postmeta wm2
                    ON (
                        wm1.meta_value = wm2.post_id
                        AND wm2.meta_key = "_wp_attached_file"
                        AND wm2.meta_value IS NOT NULL
                    )
                WHERE
                    p1.ID="'.$post['ID'].'"
                    AND p1.post_type="post"
                ORDER BY
                    p1.post_date DESC';

            $result2 = json_decode(json_encode($wpdb->get_results($sql)), true);
            foreach ($result2 as $data2) {
                if ($data2['featured_img']) {
                    $post['featured_img'] = '/' . $data2['featured_img'];
                    break;
                }
            }

            /* Find Meta Data */
            $sql = 'SELECT * FROM `' . $_pref . 'postmeta` WHERE `post_id` = ' . ((int)$post['ID']);
            $metaResult = json_decode(json_encode($wpdb->get_results($sql)), true);
            foreach ($metaResult as $metaData) {

                $metaValue = trim(isset($metaData['meta_value']) ? $metaData['meta_value'] : '');
                if (!$metaValue) {
                    continue;
                }

                switch ($metaData['meta_key']) {
                    case 'wpcf-meta-description':
                        $post['short_content'] = $metaValue;
                        break;
                    case '_yoast_wpseo_title':
                        $post['meta_title'] = $metaValue;
                        break;
                    case '_yoast_wpseo_metadesc':
                        $post['meta_description'] = $metaValue;
                        break;
                }
            }

            $creationTime = (string)$post['post_date_gmt'];

            $content = $post['post_content'];
            $content = str_replace('<!--more-->', '<!-- pagebreak -->', $content);
            $content = preg_replace(
                '/src=[\'"]((http:\/\/|https:\/\/|\/\/)(.*)|(\s|"|\')|(\/[\d\w_\-\.]*))\/wp-content\/uploads_(.*)((\.jpg|\.jpeg|\.gif|\.png|\.tiff|\.tif|\.svg)|(\s|"|\'))[\'"\s]/Ui',
                'src="$4{{media url="magefan_blog$6$8"}}$9"',
                $content
            );

            /** This filter is documented in wp-includes/post-template.php */
            $content = apply_filters( 'the_content', str_replace( ']]>', ']]&gt;', $content ) );

            $content = $this->wordpressOutoutWrap($content);

            $postViewsCount = 0;
            if (class_exists('WebberZone\Top_Ten\Counter')) {
                $postViewsCount = \WebberZone\Top_Ten\Counter::get_post_count_only($post['ID'], 'total', $blogId);
            }

            $resultPostData[] = [
                'old_id' => $post['ID'],
                'title' => $post['post_title'],
                'meta_title' => isset($post['meta_title']) ? $post['meta_title'] : '',
                'meta_description' => isset($post['meta_description']) ? $post['meta_description'] : '',
                'meta_keywords' => '',
                'identifier' => $post['post_name'],
                'content_heading' => '',
                'content' => $content,
                'short_content' => isset($post['short_content']) ? $post['short_content'] : '',
                'creation_time' => $creationTime,
                'update_time' => (string)$post['post_modified_gmt'],
                'publish_time' => $creationTime,
                'is_active' => (int)($post['post_status'] == 'publish'),
                'categories' => $postCategories,
                'tags' => $postTags,
                'featured_img' => $post['featured_img'],
                'author_id' => $post['post_author'],
                'views_count' => $postViewsCount
            ];
        }

        return $resultPostData;
    }

    public function getComments(int $offset): array
    {
        global $wpdb;

        $_pref = $wpdb->prefix;
        $offset--;

        $sql = 'SELECT
                            *
                        FROM
                            '.$_pref.'comments
                        WHERE
                            `comment_approved`=1 LIMIT '. $this->getEntitiesLimit();

        if ($offset) {
            $offset *= $this->getEntitiesLimit();
            $sql .= ' OFFSET ' . $offset;
        }

        $commentData = [];
        $result = $wpdb->get_results($sql);
        $arrayComments = json_decode(json_encode($result), true);

        foreach ($arrayComments as $comment) {
            $commentData[] = [
                'old_id' => $comment['comment_ID'],
                'parent_id' => $comment['comment_parent'],
                'post_id' => $comment['comment_post_ID'],
                'status' => 1,
                'author_type' => 0,
                'author_nickname' => $comment['comment_author'],
                'author_email' => $comment['comment_author_email'],
                'text' => $comment['comment_content'],
                'creation_time' => $comment['comment_date'],
            ];
        }

        return $commentData;
    }

    protected function wordpressOutoutWrap($pee, $br = true)
    {
        $pre_tags = [];

        if (trim($pee) === '') {
            return '';
        }

        // Just to make things a little easier, pad the end.
        $pee = $pee . "\n";

        /*
         * Pre tags shouldn't be touched by autop.
         * Replace pre tags with placeholders and bring them back after autop.
         */
        if (strpos($pee, '<pre') !== false) {
            $pee_parts = explode('</pre>', $pee);
            $last_pee  = array_pop($pee_parts);
            $pee       = '';
            $i         = 0;

            foreach ($pee_parts as $pee_part) {
                $start = strpos($pee_part, '<pre');

                // Malformed html?
                if ($start === false) {
                    $pee .= $pee_part;
                    continue;
                }

                $name              = "<pre wp-pre-tag-$i></pre>";
                $pre_tags[ $name ] = substr($pee_part, $start) . '</pre>';

                $pee .= substr($pee_part, 0, $start) . $name;
                $i++;
            }

            $pee .= $last_pee;
        }
        // Change multiple <br>s into two line breaks, which will turn into paragraphs.
        $pee = preg_replace('|<br\s*/?>\s*<br\s*/?>|', "\n\n", $pee);

        $allblocks = '(?:table|thead|tfoot|caption|col|colgroup|tbody|tr|td|th|div|dl|dd|dt|ul|ol|li|pre|form|map|area|blockquote|address|math|style|p|h[1-6]|hr|fieldset|legend|section|article|aside|hgroup|header|footer|nav|figure|figcaption|details|menu|summary)';

        // Add a double line break above block-level opening tags.
        $pee = preg_replace('!(<' . $allblocks . '[\s/>])!', "\n\n$1", $pee);

        // Add a double line break below block-level closing tags.
        $pee = preg_replace('!(</' . $allblocks . '>)!', "$1\n\n", $pee);

        // Standardize newline characters to "\n".
        $pee = str_replace([ "\r\n", "\r" ], "\n", $pee);

        // Collapse line breaks before and after <option> elements so they don't get autop'd.
        if (strpos($pee, '<option') !== false) {
            $pee = preg_replace('|\s*<option|', '<option', $pee);
            $pee = preg_replace('|</option>\s*|', '</option>', $pee);
        }

        /*
         * Collapse line breaks inside <object> elements, before <param> and <embed> elements
         * so they don't get autop'd.
         */
        if (strpos($pee, '</object>') !== false) {
            $pee = preg_replace('|(<object[^>]*>)\s*|', '$1', $pee);
            $pee = preg_replace('|\s*</object>|', '</object>', $pee);
            $pee = preg_replace('%\s*(</?(?:param|embed)[^>]*>)\s*%', '$1', $pee);
        }

        /*
         * Collapse line breaks inside <audio> and <video> elements,
         * before and after <source> and <track> elements.
         */
        if (strpos($pee, '<source') !== false || strpos($pee, '<track') !== false) {
            $pee = preg_replace('%([<\[](?:audio|video)[^>\]]*[>\]])\s*%', '$1', $pee);
            $pee = preg_replace('%\s*([<\[]/(?:audio|video)[>\]])%', '$1', $pee);
            $pee = preg_replace('%\s*(<(?:source|track)[^>]*>)\s*%', '$1', $pee);
        }

        // Collapse line breaks before and after <figcaption> elements.
        if (strpos($pee, '<figcaption') !== false) {
            $pee = preg_replace('|\s*(<figcaption[^>]*>)|', '$1', $pee);
            $pee = preg_replace('|</figcaption>\s*|', '</figcaption>', $pee);
        }

        // Remove more than two contiguous line breaks.
        $pee = preg_replace("/\n\n+/", "\n\n", $pee);

        // Split up the contents into an array of strings, separated by double line breaks.
        $pees = preg_split('/\n\s*\n/', $pee, -1, PREG_SPLIT_NO_EMPTY);

        // Reset $pee prior to rebuilding.
        $pee = '';

        // Rebuild the content as a string, wrapping every bit with a <p>.
        foreach ($pees as $tinkle) {
            $pee .= '<p>' . trim($tinkle, "\n") . "</p>\n";
        }

        // Under certain strange conditions it could create a P of entirely whitespace.
        $pee = preg_replace('|<p>\s*</p>|', '', $pee);

        // Add a closing <p> inside <div>, <address>, or <form> tag if missing.
        $pee = preg_replace('!<p>([^<]+)</(div|address|form)>!', '<p>$1</p></$2>', $pee);

        // If an opening or closing block element tag is wrapped in a <p>, unwrap it.
        $pee = preg_replace('!<p>\s*(</?' . $allblocks . '[^>]*>)\s*</p>!', '$1', $pee);

        // In some cases <li> may get wrapped in <p>, fix them.
        $pee = preg_replace('|<p>(<li.+?)</p>|', '$1', $pee);

        // If a <blockquote> is wrapped with a <p>, move it inside the <blockquote>.
        $pee = preg_replace('|<p><blockquote([^>]*)>|i', '<blockquote$1><p>', $pee);
        $pee = str_replace('</blockquote></p>', '</p></blockquote>', $pee);

        // If an opening or closing block element tag is preceded by an opening <p> tag, remove it.
        $pee = preg_replace('!<p>\s*(</?' . $allblocks . '[^>]*>)!', '$1', $pee);

        // If an opening or closing block element tag is followed by a closing <p> tag, remove it.
        $pee = preg_replace('!(</?' . $allblocks . '[^>]*>)\s*</p>!', '$1', $pee);

        // Optionally insert line breaks.
        if ($br) {

            // Normalize <br>
            $pee = str_replace([ '<br>', '<br/>' ], '<br />', $pee);

            // Replace any new line characters that aren't preceded by a <br /> with a <br />.
            $pee = preg_replace('|(?<!<br />)\s*\n|', "<br />\n", $pee);

            // Replace newline placeholders with newlines.
            $pee = str_replace('<WPPreserveNewline />', "\n", $pee);
        }

        // If a <br /> tag is after an opening or closing block tag, remove it.
        $pee = preg_replace('!(</?' . $allblocks . '[^>]*>)\s*<br />!', '$1', $pee);

        // If a <br /> tag is before a subset of opening or closing block tags, remove it.
        $pee = preg_replace('!<br />(\s*</?(?:p|li|div|dl|dd|dt|th|pre|td|ul|ol)[^>]*>)!', '$1', $pee);
        $pee = preg_replace("|\n</p>$|", '</p>', $pee);

        // Replace placeholder <pre> tags with their original content.
        if (! empty($pre_tags)) {
            $pee = str_replace(array_keys($pre_tags), array_values($pre_tags), $pee);
        }

        // Restore newlines in all elements.
        if (false !== strpos($pee, '<!-- wpnl -->')) {
            $pee = str_replace([ ' <!-- wpnl --> ', '<!-- wpnl -->' ], "\n", $pee);
        }

        // replace [caption] with <div>
        while (false !== ($p1 = strpos($pee, '[caption'))) {
            $p2 = strpos($pee, ']', $p1);

            if (false === $p2) {
                break;
            }
            $origElement = substr($pee, $p1, $p2 - $p1 + 1);
            $divElement = html_entity_decode($origElement);
            $divElement = str_replace('[caption', '<div', $divElement);
            $divElement = str_replace('align="', 'class="wp-caption ', $divElement);
            $divElement = str_replace(']', '>', $divElement);

            $pee = str_replace($origElement, $divElement, $pee);
            $pee = str_replace('[/caption]', '</div>', $pee);
        }

        // replace [video] with <div>
        while (false !== ($p1 = strpos($pee, '[video'))) {
            $p2 = strpos($pee, ']', $p1);

            if (false === $p2) {
                break;
            }
            $origElement = substr($pee, $p1, $p2 - $p1 + 1);
            $divElement = html_entity_decode($origElement);

            $source = '';

            foreach (['mp4', 'ogg'] as $format) {
                $len = strlen($format . '="');
                $x1 = strpos($divElement, $format . '="');
                if (false !== $x1) {
                    $x2 = strpos($divElement, '"', $x1 + $len);
                    if (false !== $x2) {
                        $src = substr($divElement, $x1 + $len, $x2 - $x1 - $len);
                        $source .= '<source src="' . $src . '" type="video/' . $format . '">';
                    }
                }
            }

            $divElement = str_replace('[video', '<video controls ', $divElement);
            $divElement = str_replace('align="', 'class="wp-caption ', $divElement);
            $divElement = str_replace(']', '>', $divElement);

            $pee = str_replace($origElement, $divElement . $source, $pee);
            $pee = str_replace('[/video]', '</video>', $pee);
        }


        $pee = $this->convertWordPressToShopifyYouTube($pee);

        return $pee;
    }

    protected function convertWordPressToShopifyYouTube($content) {
        // Define the regex pattern to match WordPress YouTube embeds
        $pattern = '/<figure class="wp-block-embed is-type-video is-provider-youtube wp-block-embed-youtube wp-embed-aspect-16-9 wp-has-aspect-ratio">.*?(?:https:\/\/www\.youtube\.com\/watch\?v=|https:\/\/youtu.be\/)([^\s]+).*?<\/figure>/s';

        // Replace WordPress embeds with Shopify-compatible iframes
        $content = preg_replace_callback($pattern, function ($matches) {
            $videoId = $matches[1];
            $iframeCode = '<iframe src="https://www.youtube.com/embed/' . $videoId . '?showinfo=0&amp;autoplay=1&amp;modestbranding=1" width="100%" height="315" frameborder="0" allow="autoplay"></iframe>';
            return $iframeCode;
        }, $content);

        return $content;
    }
}