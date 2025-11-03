<?php
use andreskrey\Readability\Readability;
use andreskrey\Readability\Configuration;

class newsApiModel
{

    private $db;

    private $apiKey;
    
    private $baseUrl = 'https://newsapi.org/v2/';

    private string $userAgent = 'MyNewsApp/1.0';

    public function __construct()
    {
       $this->apiKey = $_ENV['NEWS_API_KEY'] ?? '';
       $this->db = databaseModel::pdo();
       if (empty($this->apiKey)) {
            die("Ingen nyckel");
       } 
       
    }

    public function sendRequest($country, $sourceId, $q)
    {
        $endpoint = $sourceId || $country ? 'top-headlines' : 'everything';

        $params = array_filter([
            'q'        => $q,
            'sources'  => $sourceId,
            'country'  => $country,
            'pageSize' => 20,
        ], fn($v) => $v !== null);

        $url = $this->baseUrl . 'top-headlines?' . http_build_query($params);

        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => [
                'X-Api-Key: ' . $this->apiKey,
                'Accept: application/json',
            ],
            CURLOPT_USERAGENT => $this->userAgent,
            CURLOPT_TIMEOUT => 10,
        ]);

        $response = curl_exec($ch);
        $error = curl_error($ch);
        curl_close($ch);

        if ($error) {
            throw new Exception("Curl error: " . $error);
        }
        var_dump($response);
        $data = json_decode($response, true) ?? [];
        $articles = $data['articles'] ?? [];

        if ($endpoint === 'top-headlines' && $country) {
            foreach ($articles as &$a) {
                $a['country'] = $country;
            }
        }

        return $articles;
    }

    public function fetchSources(array $params = []): array
    {
        $url = $this->baseUrl . 'top-headlines/sources?' . http_build_query(array_filter([
            'category' => $params['category'] ?? null,
            'language' => $params['language'] ?? null,
            'country'  => $params['country'] ?? null,
        ], fn($v) => $v !== null));

        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => [
                'X-Api-Key: ' . $this->apiKey,
                'Accept: application/json',
            ],
            CURLOPT_USERAGENT => $this->userAgent,
            CURLOPT_TIMEOUT => 10,
        ]);
        $raw = curl_exec($ch);
        $err = curl_error($ch);
        curl_close($ch);

        if ($err || !$raw) return ['sources' => []];
        return json_decode($raw, true) ?: ['sources' => []];
    }

    public function requestArticleContent($url)
    {
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_TIMEOUT => 12,
            CURLOPT_USERAGENT => $this->userAgent,
            CURLOPT_HTTPHEADER => ['Accept:text/html,application/xhtml+xml'],
        ]);
        $html = curl_exec($ch);
        $err  = curl_error($ch);
        curl_close($ch);
        if ($err || !$html) return ['html' => null, 'text' => null];

        try {
            $conf = new Configuration();
            $readability = new Readability($conf);
            $readability->parse($html);

            $contentHtml = $readability->getContent();
        } catch (\Throwable $e) {
            //We couldn't parse the content.
            $contentHtml = '';
        }


        return $contentHtml;
    }

    public function storeSources(array $sources): int
    {
        $sql = "
            INSERT INTO sources (source_id, name, description, url, category, language, country)
            VALUES (:source_id, :name, :description, :url, :category, :language, :country)
            ON DUPLICATE KEY UPDATE
                name = VALUES(name),
                description = VALUES(description),
                url = VALUES(url),
                category = VALUES(category),
                language = VALUES(language),
                country = VALUES(country),
                updated_at = CURRENT_TIMESTAMP
        ";
        $stmt = $this->db->prepare($sql);
        $n = 0;
        foreach ($sources as $s) {
            if (empty($s['id'])) continue;
            $stmt->execute([
                ':source_id'  => $s['id'],
                ':name'       => $s['name'] ?? null,
                ':description'=> $s['description'] ?? null,
                ':url'        => $s['url'] ?? null,
                ':category'   => $s['category'] ?? null,
                ':language'   => $s['language'] ?? null,
                ':country'    => $s['country'] ?? null,
            ]);
            $n++;
        }
        return $n;
    }

    public function requestArticles($country = 'us', $source = null, $q = null)
    {

        // This part could instead be solved by a cronjob. 
        // Or even better if we had a webhook we could listen to.

        $articles = $this->getStoredArticles($country, $source, $q);
   
        //If we cant find 20 articles in the database, we try the api instead. 
        if (count($articles) < 8) {
            $articles = $this->sendRequest($country, $source, $q);
             //Store everything in database so we don't need to call the api all the time
            if ($articles) {
                $this->storeResult($articles);
            }
            //Get the articles from the database so we get our own id.
            $articles = $this->getStoredArticles($country, $source, $q);
        }
        
         return $articles ?? [];
    }

    public function storeResult($result)
    {

        $this->db->beginTransaction();
        $sql = '
            INSERT INTO articles (url, source_id, title, description, source_name, country, image_url, published_at)
            VALUES (:url, :source_id, :title, :description, :source_name, :country, :image_url, :published_at)
            ON DUPLICATE KEY UPDATE
              title = VALUES(title),
              source_id = VALUES(source_id),
              description = VALUES(description),
              source_name = VALUES(source_name),
              country = VALUES(country),
              image_url = VALUES(image_url),
              published_at = VALUES(published_at),
              updated_at = CURRENT_TIMESTAMP
        ';
        $stmt = $this->db->prepare($sql);
       
        foreach ($result as $i => $article) {
    
            $url = $article['url'];

            if (!$url) {
                continue;
            }

            $title = $article['title'] ?? '';
            $desc = $article['description'] ?? null;
            $source = $article['source']['name'] ?? null;
            $sourceId = $article['source']['id'] ?? null; 
            if (is_string($source)) {
                $source = trim($source);
            }
            $imageUrl = $article['urlToImage'] ?? null;
            $publishedAt = $article['publishedAt'] ?? null;
            $country = $article['country'] ?? null;

            $stmt->execute([
                ':url'         => $url,
                ':source_id'   => $sourceId,
                ':title'       => $title,
                ':description' => $desc,
                ':country'     => $country,
                ':source_name' => $source,
                ':image_url'   => $imageUrl,
                ':published_at'=> $publishedAt ? date('Y-m-d H:i:s', strtotime($publishedAt)) : null,
            ]);
        } 
        $this->db->commit();
    }

    //Get 20 articles based on parameters or just get the 20 last updated articles.
    public function getStoredArticles($country = null, $sourceId = null, $q = null)
    {
        
        $conds = [];
        $params = [];

        if ($q) {
            $conds[] = "MATCH(title, description, content) AGAINST(:q IN NATURAL LANGUAGE MODE)";
            $params[':q'] = $q;
        }
        if ($sourceId) {
            $conds[] = "source_id = :sid";
            $params[':sid'] = $sourceId;
        }
        if ($country) {
            $conds[] = "country = :cty";
            $params[':cty'] = $country;
        }

        $sql = "SELECT id, title, url, description, source_name, image_url, country, published_at, updated_at
        FROM articles";

        //If user searched we filter by that. If not we only get articles that wher published yesterday or today.
        if (empty($conds)) {
            $sql .= " WHERE DATE(published_at) >= (CURDATE() - INTERVAL 1 DAY)";
        } else {
            $sql .= " WHERE " . implode(' AND ', $conds);
        }

        $sql .= " ORDER BY COALESCE(published_at, updated_at) DESC
                LIMIT 20";

        $stmt = $this->db->prepare($sql);
        foreach ($params as $k => $v) {
            $stmt->bindValue($k, $v);
        }
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    //Get a single article.
    public function getStoredArticle($id)
    {
        //Check if we have content first.
        $contentSql = 'SELECT content, url
                    FROM articles
                    WHERE id = ?';
        $stmt = $this->db->prepare($contentSql);
        $stmt->execute([$id]);
        $content = $stmt->fetch();
            
        //We don't have saved content i database. Try to get it from the article.
        if (empty($content['content'])) {
            $newContent = $this->requestArticleContent($content['url']);
            if ($newContent) {
                $updateSql = 'UPDATE articles 
                            SET content = ?
                            WHERE id = ?';
                $stmt = $this->db->prepare($updateSql);
                $stmt->execute([(string)$newContent, $id]);
            }
        } 

        $sql = 'SELECT id, title, url, content, source_name, image_url, published_at
            FROM articles
            WHERE id = ?';
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$id]);
        
        $article = $stmt->fetch();

        //We don't want to trust 3rd party but need som html so the article looks nice.
        $allowed = '<p><a><strong><b><em><i><br><ul><ol><li><blockquote><h2><h3><img>';
        $content = $article['content'] ?? '';
        $article['content'] = strip_tags($content, $allowed);

        return $article;

    }

    public function getAvailableSources(): array
    {
        $stmt = $this->db->query("SELECT source_id, name FROM sources ORDER BY name ASC");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getAvailableCountries(): array
    {
        $stmt = $this->db->query("SELECT DISTINCT country FROM sources WHERE country IS NOT NULL AND country <> '' ORDER BY country ASC");
        return array_column($stmt->fetchAll(PDO::FETCH_ASSOC), 'country');
    }

    public function getComments($articleId)
    {
        $stmt = $this->db->prepare(
          "SELECT id, author, body_raw, created_at
           FROM comments
           WHERE article_id = :aid
           ORDER BY created_at DESC LIMIT 100"
        );
        $stmt->execute([':aid' => $articleId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function addComment($articleId, $comment, $ip, $ua, $author = null) 
    {   
        $ipHash = $ip ? hash_hmac('sha256', $ip, $_ENV['IP_HASH_SECRET'] ?? 'secret', true) : null;
        $stmt = $this->db->prepare(
          "INSERT INTO comments (article_id, author, body_raw, ip_hash, ua)
           VALUES (:aid, :author, :body, :iphash, :ua)"
        );
        $stmt->execute([
          ':aid' => $articleId,
          ':author' => $author,
          ':body' => $comment,
          ':iphash' => $ipHash,
          ':ua' => $ua ? mb_substr($ua,0,255) : null,
        ]);
    }


}