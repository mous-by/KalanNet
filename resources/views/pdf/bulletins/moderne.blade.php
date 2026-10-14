{{-- A4 paysage : deux exemplaires A5 du même bulletin côte à côte, séparés par un trait de coupe. --}}
<table class="deux-exemplaires">
    <tr>
        <td class="exemplaire">@include('pdf.bulletins.moderne_exemplaire')</td>
        <td class="exemplaire coupe">@include('pdf.bulletins.moderne_exemplaire')</td>
    </tr>
</table>
