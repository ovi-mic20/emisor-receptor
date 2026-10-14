<?php 
 
namespace App\Events; 
 
use Illuminate\Broadcasting\Channel; 
use Illuminate\Contracts\Broadcasting\ShouldBroadcast; 
 
class RemoteStyleUpdate implements ShouldBroadcast 
{ 
    public string $newText; 
 
    public function __construct(string $newText)
    { 
        $this->newText  = $newText; 
    }
     public function broadcastOn(): array 
    { 
        return [new Channel('style-channel')]; 
    } 
     public function broadcastAs(): string 
    { 
        return 'RemoteStyleUpdate'; 
    } 
}