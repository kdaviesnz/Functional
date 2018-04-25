<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Functional Tests</title>
</head>
<body style="font-size:12px;width:60%;margin:auto;">

   <h1>Mutated variables</h1>

   <?php echo $functionsWithMutatedVariablesHTML; ?>

   <h1>Loops</h1>

   <?php echo $functionsWithLoopsHTML; ?>

   <h1>Functions with variables that are used only once</h1>

   <?php echo $functionsWithVariablesUsedOnlyOnceHTML; ?>

   <h1>Functions that are too big.</h1>

   <?php echo $functionsThatAreTooBigHTML; ?>

   <h1>Functions that are not pure.</h1>

   <?php echo $functionsThatAreNotPureHTML; ?>

   <h1>Similar code</h1>

   <?php echo $similarFunctionsHTML; ?>



</body>
</html>