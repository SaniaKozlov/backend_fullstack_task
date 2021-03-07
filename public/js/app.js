

// define the item component
Vue.component('item', {
	template: '#item-template',
	props: {
		model: Object
	},
	data: function () {
		return {
			open: false
		}
	},
	methods: {
		addLike(id) {
			this.$parent.addLike(id, 'comment');
		},
		showForm(id) {
			this.$parent.showForm(id);
		}
	}
})


var app = new Vue({
	el: '#app',
	data: {
		login: '',
		pass: '',
		post: false,
		invalidLogin: false,
		invalidPass: false,
		invalidSum: false,
		showComment: false,
		comment: {
			message: '',
			parent: null
		},
		posts: [],
		addSum: 0,
		amount: 0,
		commentText: '',
		packs: [
			{
				id: 1,
				price: 5
			},
			{
				id: 2,
				price: 20
			},
			{
				id: 3,
				price: 50
			},
		],
	},
	computed: {
		test: function () {
			var data = [];
			return data;
		}
	},
	created(){
		var self = this
		axios
			.get('/main_page/get_all_posts')
			.then(function (response) {
				self.posts = response.data.posts;
			})
	},
	methods: {
		bus: function (data) {
			console.log(data)
			this.message = 'You right-clicked on ' + data.name
		},
		logout: function () {
			console.log ('logout');
		},
		logIn: function () {
			var self= this;
			if(self.login === ''){
				self.invalidLogin = true
			}
			else if(self.pass === ''){
				self.invalidLogin = false
				self.invalidPass = true
			}
			else{
				self.invalidLogin = false
				self.invalidPass = false
				axios.post('/main_page/login', {
					login: self.login,
					password: self.pass
				})
					.then(function (response) {
						setTimeout(function () {
							$('#loginModal').modal('hide');
						}, 500);
					})
			}
		},
		fiilIn: function () {
			var self= this;
			if(self.addSum === 0){
				self.invalidSum = true
			}
			else{
				self.invalidSum = false
				axios.post('/main_page/add_money', {
					sum: self.addSum,
				})
					.then(function (response) {
						setTimeout(function () {
							$('#addModal').modal('hide');
						}, 500);
					})
			}
		},
		openPost: function (id) {
			var self= this;
			axios
				.get('/main_page/get_post/' + id)
				.then(function (response) {
					self.post = response.data.post;
					if(self.post){
						setTimeout(function () {
							$('#postModal').modal('show');
						}, 500);
					}
				})
		},
		addLike: function (id, type) {
			var self= this;
			axios
				.get('/main_page/like/'+id+'/'+type)
				.then(function (response) {
					self.post.likes++;
				})

		},
		buyPack: function (id) {
			var self= this;
			axios.post('/main_page/buy_boosterpack', {
				id: id,
			})
				.then(function (response) {
					self.amount = response.data.amount
					if(self.amount !== 0){
						setTimeout(function () {
							$('#amountModal').modal('show');
						}, 500);
					}
				})
		},
		commentPost: function (id) {
			var self = this;
			axios.get('/main_page/comment/'+id+'/'+self.comment.message+'/'+self.comment.parent)
				.then(function (response) {
					self.post.comments.push(self.comment);
					self.comment = {message: '', parent: null}
				});
		},
		showForm: function (id) {
			if (!this.showComment) {
				this.showComment = !this.showComment;
			}

			this.comment.parent = id;
		}
	}
});

