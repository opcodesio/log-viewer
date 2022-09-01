# CONTRIBUTING

## Introduction

Hello and thank you for your interest in contributing to Log Viewer.

Contributions are welcome and there are many ways you can get involved!

To get started, choose your area of interest:

<table>
  <tr>
    <td  align="center">
        <a href="#-issues--discussions">👥 Issues & Discussions</a> |
        <a href="#-documentation">📚 Documentation</a> |
        <a href="#-spread-the-word">📣 Spread the word</a> |
        <a href="#-code-contribution">💻 Code Contribution</a>
    </td>
  </tr>
</table>

<br/>

---

### 👥 Issues & Discussions

You can interact with users by sharing information and asking/answering questions in our [Discussions](https://github.com/opcodesio/log-viewer/discussions) tab.

Also, you can contribute by reporting bugs, patching problems or providing technical support in our [Issues](https://github.com/opcodesio/log-viewer/issues) tab.

<br/>

---

### 📚 Documentation

Documentation is key for any project success!

Currently, our documentation is stored at the [README](https://github.com/opcodesio/log-viewer/blob/main/README.md) file of this repository.

You may contribute by improving existing information, covering missing topics, or fixing typos and grammar errors.

The documentation official language is in English.

<br/>

---

### 📣 Spread the word

If you enjoy Log Viewer, please consider talking about our project in your community.

Share this [repository link](https://github.com/opcodesio/log-viewer/) on Twitter, YouTube, Discord or any other social network you are part of.

You are also welcome to write articles, reviews and tutorials about this project on your blog or programming website.

Ah! Don't forget to let the author know about your work. Say hello to [@arukompas](https://github.com/arukompas).

<br/>

---

### 💻 Code Contribution

Please follow the steps below to contribute with code.

## Steps

### 📌 Step 1

Fork this repository and enter its directory. Run the command:

```shell
git clone https://github.com/opcodesio/log-viewer.git && cd log-viewer
```

### 📌 Step 2

Install all PHP dependencies using Composer, run the command:

```shell
composer install
```

Once finished, proceed to install Node dependencies. Run the command:

```shell
npm install
```

### 📌 Step 3

Create a new branch for your code. You may call it `feature-` / `fix-` / `enhancement-` followed by the name of what you are developing.

For example:

```shell
git checkout -b feature/feature-new_about_page
```

Please consider to write tests to cover your code. Tests are helpful for the code reviewers, and it reduces bugs and improves software quality.

### 📌 Step 4

After you are done coding, please run Laravel Pint for code formatting:

```Shell
composer format
```

Then, run Larastan for static analysis:

```Shell
composer analyse
```

And finally, run the Pest PHP for tests:

```Shell
composer test
```

### 📌 Step 5

Commit your changes. Please send short and descriptive commits.

For example:

```Shell
git commit -m "adds route for about page"
```

### 📌 Step 6

If all tests are passing ✅, you may push your code and submit a Pull Request.

Please write a summary of your contribution, detailing what you are changing/fixing/proposing.

When necessary, please provide usage examples, code snippets and screenshots. You may also include links related to Issues or other Pull Requests.

Once submitted, your Pull Request will be marked for review and people will send questions, comments and eventually request changes.

---

🙏 Thank you for your contribution!
