<?php 

require_once 'vendor/autoload.php';
use Google\Cloud\Firestore\FirestoreClient;


class firestore {
	protected $db;
	protected $content;

	public function __construct(){
		$this->db = new FirestoreClient([
			'projectId' => 'PROJECT_NAME',
		]);
	}

	public function getData(string $collection){

		$contents = $this->db->collection($collection);
		$snapshot = $contents->documents();

		
		foreach ($snapshot as $document) {			
			$id = $document->id();
			$name = $document->name();
			$data = $document->data()['title'];
			$data = $document->data()['date'];
			$data = $document->data()['category'];
		}
	}



	public function addContent(string $id, $content, $collection = 'contents'){

		$docRef = $this->db->collection($collection)->document($id);
		$docRef->set([
			'category' => $content['category'],
			'date' => $content['date'],
			'description' => $content['description'],
			'image url' => $content['image'],
			'loves' => $content['loves'],
			'timestamp' => $content['timestamp'],
			'title' => $content['title'] 
		]);
		
	}

	public function delete_collection($collectionReference, $batchSize){
		// DELETE BATCH
		$documents = $this->db->collection($collectionReference)->limit($batchSize)->documents();
		while (!$documents->isEmpty()) {
			foreach ($documents as $document) {
				printf('Deleting document %s' . PHP_EOL."<br>", $document->id());
				$document->reference()->delete();
			}

			break;
		}

	}

	public function delete_document($collection, $id){
		// DELETE ONE
		$this->db->collection($collection)->document($id)->delete();
	}

	public function normalizeData(string $collection, string $to_collection, $limit = 1000){
		// MOVE TO ANOTHER COLLECTION - OPSIONAL FOR ME
		$contents = $this->db->collection($collection);

		$snapshot = $contents->documents();
		$count_data = 0;

		$collection_data = [];
		foreach ($snapshot as $document) {
			$item = [];
			$item = array(
				'id' => $document->id(),
				'category' => $document->data()['category'],
				'date' => $document->data()['date'],
				'description' => $document->data()['description'],
				'image' => $document->data()['image url'],
				'loves' => $document->data()['loves'],
				'timestamp' => $document->data()['timestamp'],
				'title' => $document->data()['title']
			);

			$collection_data[] = $item;

			$count_data++;
		}

		$stored_data = count($collection_data);

		if ($stored_data < $limit) {
			// echo "Jumlah: $stored_data";
			// echo "Data kurang dari $limit<br>";
		} else {
			// echo "Jumlah: $stored_data";
			// echo "Data lebih dari $limit<br>";
			$archive_amount = $stored_data - $limit;
			array_splice($collection_data, $archive_amount);

			foreach ($collection_data as $document) {
				// echo "Data Masuk foreach<br>";
				// get id
				$id_time = substr($document['id'], 0, 10);
				$past_day = (time() - (24*60*60));

				if ($id_time < $past_day) {
					// echo "Data Archive<br>";
					$this->addContent($document['id'], $document,'contents_archive');
					$this->delete_document('contents', $document['id']);					
				}
			}
		}
		return $stored_data;
	}
}

?>
