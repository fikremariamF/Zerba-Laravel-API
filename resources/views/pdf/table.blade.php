<!DOCTYPE html>
<html>
<head>
    <title>PDF Report</title>
    <style>
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }
        th, td {
            border: 1px solid black;
            padding: 8px;
            text-align: left;
        }
    </style>
</head>
<body>
    {{-- Foams Table --}}
    <h2>Foams</h2>
    <table>
        <tr>
            <th>Date</th>
            <th>Bought</th>
            <th>Percentage</th>
            <th>Bnet</th>
        </tr>
        @foreach($data['foams'] as $foam)
            <tr>
        <td>{{ $foam->date }}</td>
        <td>{{ $foam->sold }}</td>
        <td>{{ $foam->percentage }}</td>
        <td>{{ $foam->sold - $foam->percentage }}</td>
    </tr>
        @endforeach
        <tr>
        <td></td>
        <td>{{ $data['netals']['foams']['sold'] }}</td>
        <td>{{ $data['netals']['foams']['percentage'] }}</td>
        <td>{{ $data['netals']['foams']['net'] }}</td>
    </tr>
    </table>

    {{-- Cherks Table --}}
    <h2>Cherks</h2>
    <table>
        <tr>
            <th>Date</th>
            <th>Sold</th>
            <th>Percentage</th>
            <th>Net</th>
        </tr>
        @foreach($data['cherks'] as $foam)
            <tr>
                <td>{{ $foam->date }}</td>
                <td>{{ $foam->sold }}</td>
                <td>{{ $foam->percentage }}</td>
                <td>{{ $foam->sold - $foam->percentage }}</td>
            </tr>
        @endforeach
        <tr>
        <td></td>
        <td>{{ $data['netals']['cherks']['sold'] }}</td>
        <td>{{ $data['netals']['cherks']['percentage'] }}</td>
        <td>{{ $data['netals']['cherks']['net'] }}</td>
    </tr>
    </table>
    {{-- Repeat similar structure for cherks, totals, my_costs, and ts_costs --}}
    <h2>Totals</h2>
    <table>
        <tr>
            <th>Date</th>
            <th>Sold</th>
            <th>Cherk</th>
            <th>Bergamod</th>
            <th>Net</th>
        </tr>
        @foreach($data['totals'] as $foam)
            <tr>
                <td>{{ $foam->date }}</td>
                <td>{{ $foam->sold }}</td>
                <td>{{ $foam->cherk }}</td>
                <td>{{ $foam->bergamod }}</td>
                <td>{{ $foam->sold - $foam->cherk - $foam->bergamod }}</td>
            </tr>
        @endforeach
        <tr>
        <td></td>
        <td>{{ $data['netals']['total']['sold'] }}</td>
        <td>{{ $data['netals']['total']['cherk'] }}</td>
        <td>{{ $data['netals']['total']['bergamod'] }}</td>
        <td>{{ $data['netals']['total']['net'] }}</td>
    </tr>
    </table>

    <h2>My Costs</h2>
    <table>
        <tr>
            <th>Date</th>
            <th>Spent</th>
        </tr>
        @foreach($data['my_costs'] as $foam)
            <tr>
                <td>{{ $foam->date }}</td>
                <td>{{ $foam->spent }}</td>
            </tr>
        @endforeach
        <tr>
        <td></td>
        <td>{{ $data['netals']['my-costs']['spent'] }}</td>
    </tr>
    </table>

    <h2>Ts Costs</h2>
    <table>
        <tr>
            <th>Date</th>
            <th>Spent</th>
        </tr>
        @foreach($data['ts_costs'] as $foam)
            <tr>
                <td>{{ $foam->date }}</td>
                <td>{{ $foam->spent }}</td>
            </tr>
        @endforeach
        <tr>
        <td></td>
        <td>{{ $data['netals']['ts-costs']['spent'] }}</td>
    </tr>
    </table>

    {{-- Net Table --}}
    <h2>Net Details</h2>
    <table>
        <tr>
            <th>Start Date</th>
            <th>End Date</th>
            <th>Bnet</th>
            <th>Initial Debt</th>
            <th>Snet</th>
            <th>Total Net</th>
        </tr>
        <tr>
            <td>{{ $data['net']['startDate'] }}</td>
            <td>{{ $data['net']['endDate'] }}</td>
            <td>{{ $data['net']['Bnet'] }}</td>
            <td>{{ $data['net']['initialDebt'] }}</td>
            <td>{{ $data['net']['Snet'] }}</td>
            <td>{{ $data['net']['TotNet'] }}</td>
        </tr>
    </table>

    {{-- Personal Profit Table --}}
    <h2>Personal Profit</h2>
    <table>
        <tr>
            <th>Start Date</th>
            <th>End Date</th>
            <th>My Cost</th>
            <th>My Profit</th>
            <th>Net Profit</th>
        </tr>
        <tr>
            <td>{{ $data['PersonalProfit']['startDate'] }}</td>
            <td>{{ $data['PersonalProfit']['endDate'] }}</td>
            <td>{{ $data['PersonalProfit']['Mycost'] }}</td>
            <td>{{ $data['PersonalProfit']['MyProfit'] }}</td>
            <td>{{ $data['PersonalProfit']['NetProfit'] }}</td>
        </tr>
    </table>

    {{-- Add additional tables for other data sets as needed --}}
</body>
</html>
