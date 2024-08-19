<?php

namespace App\Http\Controllers;

use App\Models\Ckeditor;
use Illuminate\Http\Request;
use Yajra\DataTables\DataTables;
use Exception;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;

class CkeditorController extends Controller
{

        public function index(){
            return view('welcome');
        }
        public function form(){

            // $post=
            // if (!empty($pos[])){
            // $data = Ckeditor::getData($post);
            // }
            return view('form');
        }

        public function data(Request $request)
        {
            try {
                
                $post = $request->all();
                $data = Ckeditor::listStudents($post); 

                $array = []; 

                foreach ($data as $index => $row) {
                    $image = asset('/images/no-image.png');
                
                    if (!empty($row->image) && file_exists(public_path('storage/images/' . $row->image))) {
                        $image = asset('storage/images/' . $row->image);
                    }
                
                    $array[$index] = [
                        "id" => $row->id,
                        "name" => $row->name,
                        "email" => $row->email,
                        "image" => '<img src="' . $image . '" height="100px" width="100px" alt="image"/>',
                        "date" => $row->date,
                        "description" => $row->description,
                        "action" => '
                            <button class="edit-btn" 
                                    data-id="' . $row->id . '" 
                                    data-name="' . $row->name . '" 
                                    data-email="' . $row->email . '" 
                                    data-image="' . $row->image . '" 
                                    data-date="' . $row->date . '" 
                                    data-description="' . htmlspecialchars($row->description) . '">
                                <i class="fa-regular fa-pen-to-square"></i>Edit
                            </button> 
                            <button class="delete-btn" data-id="' . $row->id . '"><i class="fa-solid fa-trash"></i>Delete</button>'
                    ];
                }                
                $recordsTotal = count($data);
                $recordsFiltered = $recordsTotal; 
            } catch (QueryException $e) {
                $array = [];
                $recordsTotal = 0;
                $recordsFiltered = 0;
            } catch (Exception $e) {
                $array = [];
                $recordsTotal = 0;
                $recordsFiltered = 0;
            }
            // dd($array);
            return response()->json([
                "draw" => $post['draw'],
                "recordsTotal" => $recordsTotal,
                "recordsFiltered" => $recordsFiltered,
                "data" => $array
            ]);
        }

    public function about( Request $request) {
        $post=$request->all();
        $data = Ckeditor::getData($post);
        if(!$data){
            throw new Exception("something", 1);
        }
        return response()->json($data); 
    }

    public function save(Request $request)
    {
        // Define validation rules
        $rules = [
            'id' => 'nullable|integer|exists:ckeditor,id',
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255',
            'date' => 'required|date',
            'description' => 'nullable|string',
            'image' => 'nullable|image|max:2048',
        ];
    
        // Validate the request data
        $validator = \Validator::make($request->all(), $rules);
    
        if ($validator->fails()) {
            return response()->json([
                'type' => 'error',
                'message' => $validator->errors()->first(),
            ]);
        }
    
        try {
            $post = $request->all();
            $type = 'success';
            $message = 'Records saved successfully';
    
            DB::beginTransaction();
    
            // Call the model method to save the data
            $result = Ckeditor::saveData($post);
    
            if (!$result) {
                throw new Exception('Could not save record');
            }
    
            DB::commit();
        } catch (QueryException $e) {
            DB::rollBack();
            $type = 'error';
            $message = 'Database error: ' . $e->getMessage();
        } catch (Exception $e) {
            DB::rollBack();
            $type = 'error';
            $message = $e->getMessage();
        }
    
        return response()->json(['type' => $type, 'message' => $message]);
    }
    


    public function delete(Request $request)
    {
        try {
         
            $type = 'success';
            $message = "Record deleted successfully";
            $post = $request->all();
            DB::beginTransaction();
            if (!Ckeditor::deleteData( $post)) {
                throw new Exception("Record could not be deleted");
            }

            DB::commit();
        } catch (QueryException $e) {
            DB::rollBack();
            $type = 'error';
            $message = 'Database error: ' . $e->getMessage();
        } catch (Exception $e) {
            DB::rollBack();
            $type = 'error';
            $message = $e->getMessage();
        }

        return response()->json(['type' => $type, 'message' => $message,]);
    }
}
