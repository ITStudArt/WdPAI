<?php

require_once 'AppController.php';
require_once __DIR__ . '/../models/User.php';
require_once __DIR__.'/../models/Kid.php';
require_once __DIR__.'/../repository/UserRepository.php';

class KidController extends AppController
{
    const SUPORTED_DOC_TYPES = ['application/pdf', 'application/msword', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document', 'application/vnd.oasis.opendocument.text'];
    private $messages = [];
    private $userRepository;

    public function __construct()
    {
        parent::__construct();
        $this->userRepository = new UserRepository();
    }

    public function addKid(){
       if($this->isPost()  && is_uploaded_file($_FILES['kid-diagnosis']['tmp_name'])
           && is_uploaded_file($_FILES['kid-photo']['tmp_name'])
           && $this->validate_photo($_FILES['kid-photo'])
           && $this->validate_diagnosis($_FILES['kid-diagnosis'])){
           move_uploaded_file(
                $_FILES['kid-photo']['tmp_name'],
                dirname(__DIR__).self::UPLOAD_DIRECTORY.$_FILES['kid-photo']['name']
            );
           move_uploaded_file(
               $_FILES['kid-diagnosis']['tmp_name'],
               dirname(__DIR__).self::UPLOAD_DIRECTORY.$_FILES['kid-diagnosis']['name']
           );

            $kid = new Kid($_POST['kid-name'],
                $_POST['kid-surname'],
                $_POST['kid-age'],
                $_POST['therapist'],
                $_FILES['kid-diagnosis']['name'],
                $_FILES['kid-photo']['name'],
                $_POST['parent-name'],
                $_POST['parent-surname'],
                $_POST['phone'],
                $_POST['email'],
                $_POST['password']);
            $this->userRepository->addKid($kid);
           return $this->render('kidprofile', ['messages'=>$this->messages, 'kid'=>$kid]);
       }
       $this->render('add_kid_form', ['messages'=>$this->messages]);
   }

    public function kid_profile(){
        $this->userRepository->getUser();
        $this->render('kidprofile', ['messages'=>$this->messages]);
    }
    public function searchKid(){
        $contentType = isset($_SERVER["CONTENT_TYPE"]) ? trim($_SERVER["CONTENT_TYPE"]) : '';
        if($contentType === "application/json"){
            $content = trim(file_get_contents("php://input"));
            $decoded = json_decode($content, true);
            header('Content-type: application/json');
            http_response_code(200);
            echo json_encode($this->userRepository->getKidByName($decoded['search']));
        }
    }
    public function kids()
    {
        $allKids = $this->userRepository->getKids();
        $this->render('searchfor',['messages'=>['dziecko','dziecka'],'users'=>$allKids]);
    }

    private function validate_diagnosis($file):bool{
        if($file['size']>self::MAX_FILE_SIZE){
            $this->messages[]= 'File is too large for destination file system.';
            return false;
        }
        if(!isset($file['type']) || !in_array($file['type'],self::SUPORTED_DOC_TYPES)){
            $this->messages[]= 'File type is not supported';
            return false;
        }
        return true;
    }
}