<?php


class newsApiController 
{

    public $model;
    
    public $view;

    public $newsData;

    public $sourceList;

    public $countriesList;

    public $comments;

    public function __construct() 
    {
        $this->model = new newsApiModel();
        //This would be better if we move it out to own function and add it as a cronjob.
        //Sources and countries shouldn't change often so could do it once per day or even less often.
        $sourcesResp = $this->model->fetchSources();
        $this->model->storeSources($sourcesResp['sources'] ?? []);
    }

    public function execute()
    {

        $uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
        $segments = array_values(array_filter(explode('/', trim($uri, '/'))));

        $action = $segments[0] ?? '';
        $articleId = $segments[1] ?? null;

        $q        = trim($_GET['q'] ?? '');
        $sourceId = $_GET['source'] ?? null;
        $country  = $_GET['country'] ?? 'us'; //Default to us.
        
        $this->sourceList = $this->model->getAvailableSources();
        $this->countriesList = $this->model->getAvailableCountries();

        switch ($action) {
            case 'article':
            //show article
                $this->newsData = $this->model->getStoredArticle($articleId);

                if (isset($_POST['comment'])) {
                    if (!empty($_POST['website'])) {
                        // Honeypot.
                        // Do not do anything. 
                    } else {

                        if (!hash_equals($_SESSION['csrf'] ?? '', $_POST['csrf'] ?? '')) {
                            http_response_code(403); echo "CSRF-fel."; return;
                        }

                        $author = trim($_POST['author'] ?? null);
                        $comment   = trim($_POST['comment'] ?? '');
                        $ip = $_SERVER['REMOTE_ADDR'] ?? null;
                        $ua = $_SERVER['HTTP_USER_AGENT'] ?? null;
                        if ($comment !== '' && mb_strlen($comment,'UTF-8') <= 2000) {
                            $this->model->addComment($articleId, $comment, $ip, $ua, $author);
                        }
                        
                    }
                }
                // Load comments.
                $this->comments = $this->model->getComments($articleId);
                $this->renderNews($articleId);
            break;

            case 'search':
            case 'articles':
                $this->newsData = $this->model->requestArticles($country, $sourceId, $q);
                $this->renderNews();
            break;

            default:
                 $this->newsData = $this->model->requestArticles($country, $sourceId, $q);
                 $this->renderNews();
            break;
        }

    }
    

    public function renderNews($articleId = null)
    {

        include_once('views/partials/header.php');
        if ($articleId) {
            //Show specific article.
            include_once('views/article.php');
        } else {
            include_once('views/articles.php');
        }
        include_once('views/partials/footer.php');
    }



}