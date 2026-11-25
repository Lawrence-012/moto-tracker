<?php
foreach (App\Models\Itinerary::where('unit_head_approved', false)->where('status', '!=', 'Cancelled')->get() as $i) {
    $ps = App\Support\ItineraryApprovers::passengers($i);
    $s1 = App\Support\ItineraryApprovers::step1For($i);
    echo 'ITIN '.$i->id.' status='.$i->status
        .' passengers=['.$ps->map(fn($p) => $p->id.':'.$p->first_name.' '.$p->last_name)->implode(', ').']'
        .' step1='.($s1 ? $s1->id.':'.$s1->first_name.' '.$s1->last_name : 'NONE').PHP_EOL;
}
$a = App\Models\Employee::where('email', 'anjela.tolentino@clsu2.edu.ph')->first();
echo 'ANJELA id='.($a ? $a->id : 'NOT FOUND').PHP_EOL;