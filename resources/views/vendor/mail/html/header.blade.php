@props(['url'])
<tr>
<td class="header">
<a href="{{ $url }}" style="display: inline-block;">
<img src="{{ config('app.url') }}/logo/matterlynk-horizontal-dark.svg"
     class="logo"
     alt="{{ config('app.name', 'MatterLynk') }}"
     style="height: 36px; width: auto; max-height: 36px; display: block; margin: 0 auto;">
</a>
</td>
</tr>
